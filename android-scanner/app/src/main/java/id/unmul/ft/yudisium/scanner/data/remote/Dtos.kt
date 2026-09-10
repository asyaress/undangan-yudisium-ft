package id.unmul.ft.yudisium.scanner.data.remote

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

interface MobileApi {
    @POST("api/mobile/login")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @POST("api/mobile/logout")
    suspend fun logout()

    @GET("api/mobile/events")
    suspend fun events(): EventsResponse

    @GET("api/mobile/events/{id}/roster")
    suspend fun roster(@Path("id") id: Int): RosterResponse

    @POST("api/mobile/events/{id}/scan")
    suspend fun scan(@Path("id") id: Int, @Body body: ScanDto): ScanResponse

    @POST("api/mobile/events/{id}/sync")
    suspend fun sync(@Path("id") id: Int, @Body body: SyncRequest): SyncResponse
}

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String,
)

@Serializable
data class LoginResponse(
    val token: String,
    val user: UserDto,
)

@Serializable
data class UserDto(
    val id: Int,
    val name: String,
    val email: String,
)

@Serializable
data class EventsResponse(
    val events: List<EventDto> = emptyList(),
)

@Serializable
data class EventDto(
    val id: Int,
    val name: String,
    @SerialName("event_date") val eventDate: String? = null,
    val location: String? = null,
    @SerialName("is_active") val isActive: Boolean = false,
    @SerialName("participant_count") val participantCount: Int = 0,
)

@Serializable
data class RosterResponse(
    val event: EventDto,
    val summary: SummaryDto,
    val participants: List<ParticipantDto> = emptyList(),
)

@Serializable
data class SummaryDto(
    val total: Int = 0,
    @SerialName("checked_in") val checkedIn: Int = 0,
    val remaining: Int = 0,
)

@Serializable
data class ParticipantDto(
    val id: Int,
    val nim: String,
    val name: String,
    val program: String = "-",
    @SerialName("invitation_token") val invitationToken: String = "",
    @SerialName("qr_payload") val qrPayload: String = "",
    @SerialName("rsvp_status") val rsvpStatus: String = "pending",
    @SerialName("checked_in") val checkedIn: Boolean = false,
    @SerialName("checked_in_at") val checkedInAt: String? = null,
    @SerialName("checkin_source") val checkinSource: String? = null,
)

@Serializable
data class ScanResponse(
    val ok: Boolean = true,
    val result: SyncResultDto,
    val summary: SummaryDto = SummaryDto(),
)

@Serializable
data class SyncRequest(
    val scans: List<ScanDto>,
)

@Serializable
data class ScanDto(
    @SerialName("client_scan_id") val clientScanId: String,
    @SerialName("scan_code") val scanCode: String,
    @SerialName("scanned_at") val scannedAt: String,
)

@Serializable
data class SyncResponse(
    val ok: Boolean = true,
    val results: List<SyncResultDto> = emptyList(),
    @SerialName("checked_in") val checkedIn: List<CheckedInDto> = emptyList(),
    val summary: SummaryDto = SummaryDto(),
)

@Serializable
data class SyncResultDto(
    @SerialName("client_scan_id") val clientScanId: String,
    val status: String,
    val message: String? = null,
    val participant: ParticipantDto? = null,
)

@Serializable
data class CheckedInDto(
    val id: Int,
    @SerialName("checked_in_at") val checkedInAt: String? = null,
    @SerialName("checkin_source") val checkinSource: String? = null,
)

@Serializable
data class ErrorMessage(
    val message: String? = null,
)
