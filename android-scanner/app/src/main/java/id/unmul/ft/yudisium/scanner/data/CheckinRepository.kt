package id.unmul.ft.yudisium.scanner.data

import id.unmul.ft.yudisium.scanner.data.local.AppDatabase
import id.unmul.ft.yudisium.scanner.data.local.EventEntity
import id.unmul.ft.yudisium.scanner.data.local.ParticipantEntity
import id.unmul.ft.yudisium.scanner.data.local.PendingScanEntity
import id.unmul.ft.yudisium.scanner.data.remote.ApiFactory
import id.unmul.ft.yudisium.scanner.data.remote.ErrorMessage
import id.unmul.ft.yudisium.scanner.data.remote.LoginRequest
import id.unmul.ft.yudisium.scanner.data.remote.ScanDto
import id.unmul.ft.yudisium.scanner.data.remote.SyncRequest
import id.unmul.ft.yudisium.scanner.data.remote.SyncResponse
import id.unmul.ft.yudisium.scanner.scan.QrParser
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import retrofit2.HttpException
import java.time.Instant
import java.util.UUID

data class LocalScanResult(
    val clientScanId: String,
    val status: String,
    val message: String,
    val name: String?,
    val nim: String?,
    val program: String?,
    val pending: Boolean,
)

class CheckinRepository(
    private val sessionStore: SessionStore,
    private val database: AppDatabase,
    private val apiFactory: ApiFactory,
) {
    private val json = Json { ignoreUnknownKeys = true }
    private val io = Dispatchers.IO
    private val gate = Mutex()

    val session = sessionStore.session

    fun events(): Flow<List<EventEntity>> = database.events().observe()

    fun recentScans(periodId: Int): Flow<List<PendingScanEntity>> = database.pendingScans().observeRecent(periodId)

    fun unsyncedCount(periodId: Int): Flow<Int> = database.pendingScans().observeUnsyncedCount(periodId)

    fun totalCount(periodId: Int): Flow<Int> = database.participants().observeTotal(periodId)

    fun checkedInCount(periodId: Int): Flow<Int> = database.participants().observeCheckedIn(periodId)

    suspend fun login(baseUrl: String, email: String, password: String) = withContext(io) {
        val url = normalizeBaseUrl(baseUrl)
        sessionStore.saveLogin(url, "", "", email)
        val response = apiFactory.create().login(
            LoginRequest(
                email = email.trim(),
                password = password,
                deviceName = sessionStore.deviceName(),
            ),
        )
        sessionStore.saveLogin(url, response.token, response.user.name, response.user.email)
        refreshEvents()
    }

    suspend fun leaveEvent() = withContext(io) {
        sessionStore.savePeriod(0)
    }

    suspend fun logout() = withContext(io) {
        runCatching { apiFactory.create().logout() }
        sessionStore.clearAuth()
        database.clearAllTables()
    }

    suspend fun refreshEvents() = withContext(io) {
        val response = apiFactory.create().events()
        database.events().upsertAll(
            response.events.map {
                EventEntity(
                    id = it.id,
                    name = it.name,
                    eventDate = it.eventDate,
                    location = it.location,
                    isActive = it.isActive,
                    participantCount = it.participantCount,
                )
            },
        )
    }

    suspend fun downloadRoster(periodId: Int) = withContext(io) {
        gate.withLock {
            val keepChecked = database.pendingScans().unsyncedAcceptedIds(periodId).toSet()
            val response = apiFactory.create().roster(periodId)
            database.events().upsertAll(
                listOf(
                    EventEntity(
                        id = response.event.id,
                        name = response.event.name,
                        eventDate = response.event.eventDate,
                        location = response.event.location,
                        isActive = response.event.isActive,
                        participantCount = response.summary.total,
                    ),
                ),
            )
            val locals = database.participants().forPeriod(periodId).associateBy { it.id }
            database.participants().clearPeriod(periodId)
            database.participants().upsertAll(
                response.participants.map { remote ->
                    val local = locals[remote.id]
                    val held = remote.checkedIn || keepChecked.contains(remote.id) || local?.checkedIn == true
                    ParticipantEntity(
                        id = remote.id,
                        periodId = periodId,
                        nim = remote.nim,
                        name = remote.name,
                        program = remote.program,
                        invitationToken = remote.invitationToken,
                        qrPayload = remote.qrPayload,
                        rsvpStatus = remote.rsvpStatus,
                        checkedIn = held,
                        checkedInAt = remote.checkedInAt ?: local?.checkedInAt,
                        checkinSource = remote.checkinSource ?: local?.checkinSource,
                    )
                },
            )
            sessionStore.savePeriod(periodId)
        }
    }

    suspend fun recordScan(periodId: Int, rawCode: String): LocalScanResult = withContext(io) {
        val clientScanId = gate.withLock {
            val id = recordLocked(periodId, rawCode)
            runCatching { syncLocked(periodId) }
            id
        }
        val row = database.pendingScans().find(clientScanId)
        val match = row?.participantId?.let { id ->
            database.participants().forPeriod(periodId).firstOrNull { it.id == id }
        }
        toResult(row, match, pending = row?.synced != true)
    }

    suspend fun sync(periodId: Int) = withContext(io) {
        gate.withLock { syncLocked(periodId) }
    }

    suspend fun event(periodId: Int): EventEntity? = database.events().find(periodId)

    fun apiError(error: Throwable): String {
        if (error is HttpException) {
            val raw = error.response()?.errorBody()?.string().orEmpty()
            runCatching { json.decodeFromString<ErrorMessage>(raw).message }.getOrNull()?.let { return it }
            if (error.code() == 401) return "Sesi habis. Masuk ulang."
        }
        return error.message ?: "Tidak bisa terhubung ke server."
    }

    private suspend fun recordLocked(periodId: Int, rawCode: String): String {
        val code = QrParser.lookupCode(rawCode)
        database.pendingScans().findUnsyncedCode(periodId, code)?.let { return it.clientScanId }

        val match = matchParticipant(periodId, code)
        val now = Instant.now().toString()
        val claimed = if (match != null) {
            database.participants().claimCheckin(match.id, now, "mobile")
        } else {
            0
        }
        val status = when {
            match == null -> "not_found"
            claimed == 0 -> "duplicate"
            else -> "accepted"
        }
        val scan = PendingScanEntity(
            clientScanId = UUID.randomUUID().toString(),
            periodId = periodId,
            scanCode = code,
            scannedAt = now,
            localStatus = status,
            participantId = match?.id,
            participantName = match?.name,
            participantNim = match?.nim,
            message = messageFor(status, pending = true),
            synced = false,
            lastError = null,
        )
        database.pendingScans().insert(scan)
        return scan.clientScanId
    }

    private suspend fun syncLocked(periodId: Int) {
        val pending = database.pendingScans().unsynced(periodId)
        val response = apiFactory.create().sync(
            periodId,
            SyncRequest(
                scans = pending.map {
                    ScanDto(
                        clientScanId = it.clientScanId,
                        scanCode = it.scanCode,
                        scannedAt = it.scannedAt,
                    )
                },
            ),
        )
        response.results.forEach { result ->
            database.pendingScans().markSynced(
                id = result.clientScanId,
                status = result.status,
                message = messageFor(result.status, pending = false, fallback = result.message),
            )
        }
        reconcileCheckedIn(periodId, response)
    }

    private suspend fun reconcileCheckedIn(periodId: Int, response: SyncResponse) {
        val pendingLocal = database.pendingScans().unsyncedAcceptedIds(periodId).toSet()
        val remote = response.checkedIn.associateBy { it.id }
        val rows = database.participants().forPeriod(periodId)
        database.participants().upsertAll(
            rows.map { participant ->
                val server = remote[participant.id]
                val held = server != null || pendingLocal.contains(participant.id)
                participant.copy(
                    checkedIn = held,
                    checkedInAt = server?.checkedInAt ?: participant.checkedInAt,
                    checkinSource = server?.checkinSource ?: participant.checkinSource,
                )
            },
        )
    }

    private suspend fun matchParticipant(periodId: Int, code: String): ParticipantEntity? {
        database.participants().match(periodId, code)?.let { return it }
        if (code.startsWith("YFT|")) {
            val parts = code.split("|")
            if (parts.size == 4) {
                return database.participants().forPeriod(periodId).firstOrNull { participant ->
                    participant.id == parts[2].toIntOrNull() && participant.invitationToken == parts[3]
                }
            }
        }
        return database.participants().forPeriod(periodId).firstOrNull { it.nim == code }
    }

    private fun toResult(row: PendingScanEntity?, match: ParticipantEntity?, pending: Boolean): LocalScanResult {
        val status = row?.localStatus ?: "not_found"
        return LocalScanResult(
            clientScanId = row?.clientScanId ?: "",
            status = status,
            message = row?.message ?: messageFor(status, pending),
            name = match?.name ?: row?.participantName,
            nim = match?.nim ?: row?.participantNim,
            program = match?.program,
            pending = pending && status != "not_found",
        )
    }

    private fun messageFor(status: String, pending: Boolean, fallback: String? = null): String {
        return when (status) {
            "accepted" -> if (pending) "Tersimpan di HP. Akan dikirim saat online." else "Check-in tersimpan."
            "duplicate" -> "Mahasiswa ini sudah check-in. Tidak dihitung dua kali."
            "not_found" -> "QR atau NIM tidak ada di data event ini."
            else -> fallback ?: "Tidak bisa memproses scan."
        }
    }

    private fun normalizeBaseUrl(raw: String): String {
        val trimmed = raw.trim().trimEnd('/')
        return if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
            trimmed
        } else {
            "http://$trimmed"
        }
    }
}
