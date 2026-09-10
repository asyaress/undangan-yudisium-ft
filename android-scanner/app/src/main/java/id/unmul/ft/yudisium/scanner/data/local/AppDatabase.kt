package id.unmul.ft.yudisium.scanner.data.local

import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.RoomDatabase
import kotlinx.coroutines.flow.Flow

@Entity(tableName = "events")
data class EventEntity(
    @PrimaryKey val id: Int,
    val name: String,
    val eventDate: String?,
    val location: String?,
    val isActive: Boolean,
    val participantCount: Int,
)

@Entity(tableName = "participants")
data class ParticipantEntity(
    @PrimaryKey val id: Int,
    val periodId: Int,
    val nim: String,
    val name: String,
    val program: String,
    val invitationToken: String,
    val qrPayload: String,
    val rsvpStatus: String,
    val checkedIn: Boolean,
    val checkedInAt: String?,
    val checkinSource: String?,
)

@Entity(tableName = "pending_scans")
data class PendingScanEntity(
    @PrimaryKey val clientScanId: String,
    val periodId: Int,
    val scanCode: String,
    val scannedAt: String,
    val localStatus: String,
    val participantId: Int?,
    val participantName: String?,
    val participantNim: String?,
    val message: String,
    val synced: Boolean,
    val lastError: String?,
)

@Dao
interface EventDao {
    @Query("SELECT * FROM events ORDER BY isActive DESC, eventDate DESC")
    fun observe(): Flow<List<EventEntity>>

    @Query("SELECT * FROM events WHERE id = :id LIMIT 1")
    suspend fun find(id: Int): EventEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(items: List<EventEntity>)

    @Query("DELETE FROM events")
    suspend fun clear()
}

@Dao
interface ParticipantDao {
    @Query("SELECT * FROM participants WHERE periodId = :periodId")
    suspend fun forPeriod(periodId: Int): List<ParticipantEntity>

    @Query("SELECT * FROM participants WHERE periodId = :periodId AND (qrPayload = :code OR nim = :code OR invitationToken = :code) LIMIT 1")
    suspend fun match(periodId: Int, code: String): ParticipantEntity?

    @Query("SELECT * FROM participants WHERE id = :id LIMIT 1")
    suspend fun find(id: Int): ParticipantEntity?

    @Query("SELECT * FROM participants WHERE periodId = :periodId AND id = :id AND invitationToken = :token LIMIT 1")
    suspend fun matchQr(periodId: Int, id: Int, token: String): ParticipantEntity?

    @Query("SELECT COUNT(*) FROM participants WHERE periodId = :periodId")
    fun observeTotal(periodId: Int): Flow<Int>

    @Query("SELECT COUNT(*) FROM participants WHERE periodId = :periodId AND checkedIn = 1")
    fun observeCheckedIn(periodId: Int): Flow<Int>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsertAll(items: List<ParticipantEntity>)

    @Query("UPDATE participants SET checkedIn = 1, checkedInAt = :checkedInAt, checkinSource = :source WHERE id = :id AND checkedIn = 0")
    suspend fun claimCheckin(id: Int, checkedInAt: String?, source: String?): Int

    @Query("UPDATE participants SET checkedIn = 1, checkedInAt = COALESCE(checkedInAt, :checkedInAt), checkinSource = COALESCE(checkinSource, :source) WHERE id = :id")
    suspend fun markCheckedIn(id: Int, checkedInAt: String?, source: String?)

    @Query("DELETE FROM participants WHERE periodId = :periodId")
    suspend fun clearPeriod(periodId: Int)
}

@Dao
interface PendingScanDao {
    @Insert(onConflict = OnConflictStrategy.IGNORE)
    suspend fun insert(item: PendingScanEntity): Long

    @Query("SELECT * FROM pending_scans WHERE periodId = :periodId AND scanCode = :code AND synced = 0 LIMIT 1")
    suspend fun findUnsyncedCode(periodId: Int, code: String): PendingScanEntity?

    @Query("SELECT * FROM pending_scans WHERE clientScanId = :id LIMIT 1")
    suspend fun find(id: String): PendingScanEntity?

    @Query("SELECT participantId FROM pending_scans WHERE periodId = :periodId AND synced = 0 AND localStatus = 'accepted' AND participantId IS NOT NULL")
    suspend fun unsyncedAcceptedIds(periodId: Int): List<Int>

    @Query("SELECT * FROM pending_scans WHERE periodId = :periodId AND synced = 0 ORDER BY scannedAt ASC")
    suspend fun unsynced(periodId: Int): List<PendingScanEntity>

    @Query("SELECT * FROM pending_scans WHERE periodId = :periodId ORDER BY scannedAt DESC LIMIT 30")
    fun observeRecent(periodId: Int): Flow<List<PendingScanEntity>>

    @Query("SELECT COUNT(*) FROM pending_scans WHERE periodId = :periodId AND synced = 0")
    fun observeUnsyncedCount(periodId: Int): Flow<Int>

    @Query("UPDATE pending_scans SET synced = 1, localStatus = :status, message = :message, lastError = NULL WHERE clientScanId = :id")
    suspend fun markSynced(id: String, status: String, message: String)

    @Query("UPDATE pending_scans SET lastError = :error WHERE clientScanId = :id")
    suspend fun markError(id: String, error: String)
}

@Database(
    entities = [EventEntity::class, ParticipantEntity::class, PendingScanEntity::class],
    version = 1,
    exportSchema = false,
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun events(): EventDao
    abstract fun participants(): ParticipantDao
    abstract fun pendingScans(): PendingScanDao
}
