package id.unmul.ft.yudisium.scanner.ui

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.spring
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.CalendarToday
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.OfflineBolt
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import id.unmul.ft.yudisium.scanner.R
import id.unmul.ft.yudisium.scanner.data.EventCacheStats
import id.unmul.ft.yudisium.scanner.data.LocalScanResult
import id.unmul.ft.yudisium.scanner.data.local.EventEntity
import id.unmul.ft.yudisium.scanner.scan.CameraPreview
import id.unmul.ft.yudisium.scanner.ui.theme.Accent
import id.unmul.ft.yudisium.scanner.ui.theme.Bad
import id.unmul.ft.yudisium.scanner.ui.theme.Canvas
import id.unmul.ft.yudisium.scanner.ui.theme.EventOrange
import id.unmul.ft.yudisium.scanner.ui.theme.Glass
import id.unmul.ft.yudisium.scanner.ui.theme.Good
import id.unmul.ft.yudisium.scanner.ui.theme.Ink
import id.unmul.ft.yudisium.scanner.ui.theme.InverseOnSurface
import id.unmul.ft.yudisium.scanner.ui.theme.InverseSurface
import id.unmul.ft.yudisium.scanner.ui.theme.Label
import id.unmul.ft.yudisium.scanner.ui.theme.OnPrimary
import id.unmul.ft.yudisium.scanner.ui.theme.Panel
import id.unmul.ft.yudisium.scanner.ui.theme.PrimaryButton
import id.unmul.ft.yudisium.scanner.ui.theme.PrimaryContainer
import id.unmul.ft.yudisium.scanner.ui.theme.Pressable
import id.unmul.ft.yudisium.scanner.ui.theme.SurfaceContainer
import id.unmul.ft.yudisium.scanner.ui.theme.SurfaceContainerLow
import id.unmul.ft.yudisium.scanner.ui.theme.TextAction
import id.unmul.ft.yudisium.scanner.ui.theme.Warn
import id.unmul.ft.yudisium.scanner.ui.theme.rememberAppLayout
import id.unmul.ft.yudisium.scanner.ui.theme.rememberReduceMotion
import androidx.compose.foundation.Image

@Composable
fun AppRoot(
    state: ScanUiState,
    onRefreshEvents: () -> Unit,
    onOpenEvent: (Int) -> Unit,
    onScan: (String) -> Unit,
    onSync: () -> Unit,
    onLeaveEvent: () -> Unit,
    onClearLocalData: () -> Unit,
    onDismissResult: () -> Unit,
    onDismissNotice: () -> Unit,
) {
    when (state.session.periodId) {
        0 -> EventsScreen(state, onRefreshEvents, onOpenEvent, onClearLocalData, onDismissNotice)
        else -> ScanScreen(state, onScan, onSync, onLeaveEvent, onDismissResult)
    }
}

@Composable
private fun EventsScreen(
    state: ScanUiState,
    onRefreshEvents: () -> Unit,
    onOpenEvent: (Int) -> Unit,
    onClearLocalData: () -> Unit,
    onDismissNotice: () -> Unit,
) {
    val layout = rememberAppLayout()
    val active = state.events.filter { it.isActive }
    val archives = state.events.filterNot { it.isActive }
    val operator = state.session.name.ifBlank { "Panitia registrasi" }
    val anyOffline = state.eventStats.values.any { it.rosterDownloaded }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Canvas)
            .statusBarsPadding()
            .navigationBarsPadding(),
    ) {
        LazyColumn(
            modifier = Modifier
                .weight(1f)
                .widthIn(max = layout.contentMax)
                .align(Alignment.CenterHorizontally)
                .padding(horizontal = layout.pagePad),
            verticalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            item {
                Spacer(Modifier.height(8.dp))
                OperatorPanel(operator, onRefreshEvents, onClearLocalData, state.loading)
            }
            item {
                EventsHeroBanner()
            }
            if (state.notice != null) {
                item {
                    ErrorBanner(state.notice, onDismiss = onDismissNotice)
                }
            }
            items(active, key = { it.id }) { event ->
                ActiveEventCard(
                    event = event,
                    stats = state.eventStats[event.id],
                    loading = state.loading,
                    onOpen = { onOpenEvent(event.id) },
                )
            }
            if (anyOffline) {
                item { OfflineRosterBanner(state.eventStats, state.events) }
            }
            if (archives.isNotEmpty()) {
                item {
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text("Arsip acara", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Text("Hanya baca", style = MaterialTheme.typography.labelSmall, color = Label)
                    }
                }
                items(archives, key = { it.id }) { event ->
                    ArchiveEventCard(
                        event = event,
                        stats = state.eventStats[event.id],
                        enabled = !state.loading,
                        onOpen = { onOpenEvent(event.id) },
                    )
                }
            }
            item {
                Pressable(onClick = onRefreshEvents, enabled = !state.loading, modifier = Modifier.fillMaxWidth()) { mod ->
                    Row(
                        mod
                            .fillMaxWidth()
                            .height(48.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(Panel),
                        horizontalArrangement = Arrangement.Center,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Icon(Icons.Default.Sync, contentDescription = null, tint = Ink, modifier = Modifier.size(20.dp))
                        Spacer(Modifier.size(8.dp))
                        Text("Perbarui daftar event", style = MaterialTheme.typography.titleMedium)
                    }
                }
                Spacer(Modifier.height(24.dp))
            }
        }
    }
}

@Composable
private fun OperatorPanel(operator: String, onRefresh: () -> Unit, onClearLocalData: () -> Unit, loading: Boolean) {
    Column(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(Panel)
            .padding(14.dp),
    ) {
        Text(
            "Universitas Mulawarman · Fakultas Teknik",
            style = MaterialTheme.typography.labelSmall,
            color = Label,
        )
        Text(
            operator,
            style = MaterialTheme.typography.titleMedium,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
            modifier = Modifier.padding(top = 4.dp),
        )
        Row(Modifier.padding(top = 10.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Pressable(onClick = onRefresh, enabled = !loading) { mod ->
                Row(
                    mod
                        .clip(RoundedCornerShape(8.dp))
                        .background(SurfaceContainerLow)
                        .padding(horizontal = 10.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(Icons.Default.Sync, contentDescription = null, tint = Ink, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.size(4.dp))
                    Text("Perbarui", style = MaterialTheme.typography.labelLarge, color = Ink)
                }
            }
            Pressable(onClick = onClearLocalData, enabled = !loading) { mod ->
                Row(
                    mod
                        .clip(RoundedCornerShape(8.dp))
                        .background(id.unmul.ft.yudisium.scanner.ui.theme.ErrorContainer)
                        .padding(horizontal = 10.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(Icons.Default.OfflineBolt, contentDescription = null, tint = Bad, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.size(4.dp))
                    Text("Hapus data lokal", style = MaterialTheme.typography.labelLarge, color = Bad)
                }
            }
        }
    }
}

@Composable
private fun EventsHeroBanner() {
    Box(
        Modifier
            .fillMaxWidth()
            .height(112.dp)
            .clip(RoundedCornerShape(14.dp)),
    ) {
        Image(
            painter = painterResource(R.drawable.backdrop_yudisium),
            contentDescription = null,
            modifier = Modifier.fillMaxSize(),
            contentScale = ContentScale.Crop,
        )
        Box(
            Modifier
                .fillMaxSize()
                .background(InverseSurface.copy(alpha = 0.45f))
                .padding(14.dp),
            contentAlignment = Alignment.BottomStart,
        ) {
            Column {
                Text("Pilih acara", style = MaterialTheme.typography.labelSmall, color = EventOrange)
                Text("Yudisium", style = MaterialTheme.typography.headlineSmall, color = Panel)
            }
        }
    }
}

@Composable
private fun ActiveEventCard(
    event: EventEntity,
    stats: EventCacheStats?,
    loading: Boolean,
    onOpen: () -> Unit,
) {
    val total = stats?.localTotal?.takeIf { it > 0 } ?: event.participantCount
    val checked = stats?.checkedIn ?: 0
    val progress = if (total > 0) checked.toFloat() / total else 0f

    Column(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(Panel),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                Text(
                    "Aktif",
                    Modifier
                        .clip(RoundedCornerShape(999.dp))
                        .background(EventOrange.copy(alpha = 0.12f))
                        .padding(horizontal = 10.dp, vertical = 4.dp),
                    style = MaterialTheme.typography.labelLarge,
                    color = EventOrange,
                    fontWeight = FontWeight.SemiBold,
                )
                if (stats?.rosterDownloaded == true) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.CloudDone, contentDescription = null, tint = Label, modifier = Modifier.size(14.dp))
                        Spacer(Modifier.size(4.dp))
                        Text("Roster siap", style = MaterialTheme.typography.labelSmall, color = Label)
                    }
                }
            }
            Text(
                event.name,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 10.dp),
            )
            EventMetaBlock(event)
            if (total > 0) {
                Row(
                    Modifier
                        .fillMaxWidth()
                        .padding(top = 10.dp)
                        .clip(RoundedCornerShape(10.dp))
                        .background(SurfaceContainerLow)
                        .padding(10.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text("Check-in terverifikasi", style = MaterialTheme.typography.labelSmall, color = Label)
                    Text(
                        "$checked/$total",
                        style = MaterialTheme.typography.titleMedium,
                        color = Ink,
                        fontWeight = FontWeight.Bold,
                    )
                }
                LinearProgressIndicator(
                    progress = { progress },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp)
                        .height(3.dp)
                        .clip(RoundedCornerShape(999.dp)),
                    color = EventOrange,
                    trackColor = SurfaceContainer,
                )
            }
            Pressable(onClick = onOpen, enabled = !loading, modifier = Modifier.fillMaxWidth().padding(top = 14.dp)) { mod ->
                Row(
                    mod
                        .fillMaxWidth()
                        .height(48.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(PrimaryContainer),
                    horizontalArrangement = Arrangement.Center,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text("Buka scanner check-in", color = OnPrimary, style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.size(6.dp))
                    Icon(Icons.AutoMirrored.Filled.ArrowForward, contentDescription = null, tint = OnPrimary, modifier = Modifier.size(20.dp))
                }
            }
        }
    }
}

@Composable
private fun ArchiveEventCard(
    event: EventEntity,
    stats: EventCacheStats?,
    enabled: Boolean,
    onOpen: () -> Unit,
) {
    val total = stats?.localTotal?.takeIf { it > 0 } ?: event.participantCount
    val checked = stats?.checkedIn ?: 0
    Pressable(onClick = onOpen, enabled = enabled, modifier = Modifier.fillMaxWidth()) { mod ->
        Column(
            mod
                .fillMaxWidth()
                .clip(RoundedCornerShape(14.dp))
                .background(Panel)
                .padding(14.dp),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(
                    "Arsip",
                    Modifier
                        .clip(RoundedCornerShape(999.dp))
                        .background(SurfaceContainer)
                        .padding(horizontal = 10.dp, vertical = 4.dp),
                    style = MaterialTheme.typography.labelLarge,
                    color = Label,
                )
                if (total > 0) {
                    Text("$checked/$total hadir", style = MaterialTheme.typography.labelSmall, color = Label)
                }
            }
            Text(event.name, style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 8.dp), maxLines = 2, overflow = TextOverflow.Ellipsis)
            EventMetaBlock(event, compact = true)
        }
    }
}

@Composable
private fun EventMetaBlock(event: EventEntity, compact: Boolean = false) {
    Column(Modifier.padding(top = if (compact) 6.dp else 10.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
        event.eventDate?.let { MetaLine(Icons.Default.CalendarToday, it) }
        event.location?.let { MetaLine(Icons.Default.LocationOn, it) }
        MetaLine(Icons.Default.Groups, "${event.participantCount} mahasiswa")
    }
}

@Composable
private fun MetaLine(icon: androidx.compose.ui.graphics.vector.ImageVector, text: String) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        Icon(icon, contentDescription = null, tint = Label, modifier = Modifier.size(17.dp))
        Text(text, style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
    }
}

@Composable
private fun OfflineRosterBanner(stats: Map<Int, EventCacheStats>, events: List<EventEntity>) {
    val downloaded = events.filter { stats[it.id]?.rosterDownloaded == true }
    val totalStudents = downloaded.sumOf { stats[it.id]?.localTotal ?: 0 }
    Row(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(SurfaceContainerLow)
            .padding(12.dp),
        horizontalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Icon(Icons.Default.OfflineBolt, contentDescription = null, tint = EventOrange, modifier = Modifier.size(22.dp))
        Column {
            Text("Mode offline", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold, color = Ink)
            Text(
                "Data $totalStudents mahasiswa tersimpan di HP. Scan tetap jalan saat sinyal terputus.",
                style = MaterialTheme.typography.bodyMedium,
                color = Label,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
    }
}

@Composable
private fun ScanScreen(
    state: ScanUiState,
    onScan: (String) -> Unit,
    onSync: () -> Unit,
    onLeaveEvent: () -> Unit,
    onDismissResult: () -> Unit,
) {
    val context = LocalContext.current
    var hasCamera by remember {
        mutableStateOf(ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED)
    }
    val permission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { hasCamera = it }
    LaunchedEffect(Unit) {
        if (!hasCamera) permission.launch(Manifest.permission.CAMERA)
    }
    var nim by remember { mutableStateOf("") }
    val reduceMotion = rememberReduceMotion()
    val layout = rememberAppLayout()
    val chromePad = if (layout.landscape) 12.dp else 16.dp

    LaunchedEffect(state.result?.clientScanId, state.result?.status) {
        state.result?.let { vibrateFor(context, it.status) }
    }

    Box(Modifier.fillMaxSize().background(Color.Black)) {
        if (hasCamera) {
            CameraPreview(enabled = true, modifier = Modifier.fillMaxSize(), onBarcode = onScan)
        }
        Box(
            Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        listOf(Color.Black.copy(alpha = 0.42f), Color.Transparent, Color.Black.copy(alpha = 0.62f)),
                    ),
                ),
        )
        if (layout.landscape) {
            Row(Modifier.fillMaxSize().statusBarsPadding().navigationBarsPadding().imePadding()) {
                Box(Modifier.weight(1f).fillMaxHeight()) {
                    ScanCameraChrome(state, layout, onSync)
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        ScanViewfinder(layout.frame)
                    }
                }
                ScanSidePanel(state, nim, { nim = it }, onScan, onLeaveEvent, onSync)
            }
        } else {
            Column(
                Modifier
                    .fillMaxSize()
                    .statusBarsPadding()
                    .navigationBarsPadding()
                    .imePadding()
                    .padding(chromePad),
            ) {
                ScanCameraChrome(state, layout, onSync)
                Box(Modifier.weight(1f).fillMaxWidth(), contentAlignment = Alignment.Center) {
                    ScanViewfinder(layout.frame)
                }
                NimSearchRow(nim, { nim = it }, onScan)
                TextAction("‹ Event", onLeaveEvent, color = InverseOnSurface.copy(alpha = 0.92f))
                Text(
                    "Auto-sync aktif (~15 detik)",
                    style = MaterialTheme.typography.labelSmall,
                    color = InverseOnSurface.copy(alpha = 0.65f),
                    modifier = Modifier.padding(top = 4.dp, bottom = 4.dp),
                )
            }
        }

        AnimatedVisibility(
            visible = state.result != null,
            enter = if (reduceMotion) fadeIn() else fadeIn(spring(dampingRatio = 1f)) + slideInVertically(spring(stiffness = 380f)) { it / 2 },
            exit = if (reduceMotion) fadeOut() else fadeOut(spring(dampingRatio = 1f)) + slideOutVertically(spring(stiffness = 380f)) { it / 2 },
            modifier = Modifier.fillMaxSize(),
        ) {
            state.result?.let { result ->
                Box(Modifier.fillMaxSize()) {
                    Box(
                        Modifier
                            .fillMaxSize()
                            .background(Color.Black.copy(alpha = 0.32f))
                            .clickable { onDismissResult() },
                    )
                    ResultSheet(
                        result = result,
                        onDismiss = onDismissResult,
                        modifier = Modifier
                            .align(Alignment.BottomCenter)
                            .fillMaxWidth()
                            .widthIn(max = 420.dp),
                    )
                }
            }
        }

        if (state.loading) {
            CircularProgressIndicator(
                modifier = Modifier.align(Alignment.TopCenter).padding(top = 88.dp),
                color = EventOrange,
                strokeWidth = 2.dp,
            )
        }
    }
}

@Composable
private fun ScanCameraChrome(
    state: ScanUiState,
    layout: id.unmul.ft.yudisium.scanner.ui.theme.AppLayout,
    onSync: () -> Unit,
) {
    Column {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
            Row(
                Modifier
                    .weight(1f)
                    .clip(RoundedCornerShape(14.dp))
                    .background(Glass)
                    .padding(horizontal = 12.dp, vertical = 8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Image(
                    painter = painterResource(R.drawable.logo_unmul),
                    contentDescription = null,
                    modifier = Modifier.size(if (layout.landscape) 22.dp else 26.dp),
                    contentScale = ContentScale.Fit,
                )
                Spacer(Modifier.size(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(
                        state.eventName.ifBlank { "Scan" },
                        color = Ink,
                        style = MaterialTheme.typography.titleMedium,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    Text("${state.checkedIn}/${state.total} hadir", color = Label, style = MaterialTheme.typography.bodyMedium)
                }
            }
            if (state.pendingCount > 0) {
                StatusChip(text = "${state.pendingCount}", tone = Warn, onClick = onSync)
            } else {
                StatusChip(text = "Siap", tone = InverseSurface, onClick = onSync)
            }
        }
        state.notice?.let {
            Spacer(Modifier.height(8.dp))
            Text(it, color = InverseOnSurface, style = MaterialTheme.typography.bodyMedium)
        }
    }
}

@Composable
private fun ScanSidePanel(
    state: ScanUiState,
    nim: String,
    onNimChange: (String) -> Unit,
    onScan: (String) -> Unit,
    onLeaveEvent: () -> Unit,
    onSync: () -> Unit,
) {
    Column(
        Modifier
            .widthIn(min = 260.dp, max = 300.dp)
            .fillMaxHeight()
            .background(Glass)
            .padding(14.dp),
        verticalArrangement = Arrangement.SpaceBetween,
    ) {
        Column {
            Text(state.eventName, style = MaterialTheme.typography.titleMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Text(
                "${state.checkedIn} / ${state.total} hadir",
                style = MaterialTheme.typography.headlineSmall,
                color = Ink,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 4.dp),
            )
            if (state.pendingCount > 0) {
                Text("${state.pendingCount} menunggu kirim", style = MaterialTheme.typography.labelSmall, color = Warn, modifier = Modifier.padding(top = 6.dp))
            }
            HorizontalDivider(Modifier.padding(vertical = 12.dp), color = SurfaceContainer)
            NimSearchRow(nim, onNimChange, onScan)
        }
        Column {
            TextAction("‹ Event", onLeaveEvent, color = Accent)
            Pressable(onClick = onSync, modifier = Modifier.fillMaxWidth().padding(top = 8.dp)) { mod ->
                Row(
                    mod
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(10.dp))
                        .background(EventOrange)
                        .padding(vertical = 10.dp),
                    horizontalArrangement = Arrangement.Center,
                ) {
                    Icon(Icons.Default.Sync, contentDescription = null, tint = OnPrimary, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.size(6.dp))
                    Text("Sinkron sekarang", color = OnPrimary, style = MaterialTheme.typography.labelLarge)
                }
            }
        }
    }
}

@Composable
private fun NimSearchRow(nim: String, onNimChange: (String) -> Unit, onScan: (String) -> Unit) {
    Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        OutlinedTextField(
            value = nim,
            onValueChange = onNimChange,
            placeholder = { Text("NIM jika QR rusak") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number, imeAction = ImeAction.Done),
            keyboardActions = KeyboardActions(onDone = {
                if (nim.isNotBlank()) {
                    onScan(nim.trim())
                    onNimChange("")
                }
            }),
            modifier = Modifier.weight(1f),
            shape = RoundedCornerShape(12.dp),
            colors = scanFieldColors(),
        )
        Pressable(onClick = {
            if (nim.isNotBlank()) {
                onScan(nim.trim())
                onNimChange("")
            }
        }) { mod ->
            Row(
                mod
                    .clip(RoundedCornerShape(12.dp))
                    .background(EventOrange)
                    .padding(horizontal = 12.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(Icons.Default.Search, contentDescription = null, tint = OnPrimary, modifier = Modifier.size(18.dp))
                Spacer(Modifier.size(4.dp))
                Text("Cari", color = OnPrimary, style = MaterialTheme.typography.labelLarge)
            }
        }
    }
}

@Composable
private fun ResultSheet(result: LocalScanResult, onDismiss: () -> Unit, modifier: Modifier = Modifier) {
    val (tone, title, icon) = when (result.status) {
        "accepted" -> Triple(
            Good,
            if (result.pending) "Hadir · menunggu" else "Hadir",
            Icons.Default.Badge,
        )
        "duplicate" -> Triple(Warn, "Sudah check-in", Icons.Default.Sync)
        else -> Triple(Bad, "Tidak ditemukan", Icons.Default.Search)
    }
    Column(
        modifier
            .navigationBarsPadding()
            .padding(horizontal = 16.dp, vertical = 12.dp)
            .clip(RoundedCornerShape(20.dp))
            .background(Panel)
            .clickable(indication = null, interactionSource = remember { MutableInteractionSource() }) {}
            .padding(horizontal = 20.dp, vertical = 16.dp),
    ) {
        Box(
            Modifier
                .align(Alignment.CenterHorizontally)
                .size(width = 36.dp, height = 5.dp)
                .clip(RoundedCornerShape(99.dp))
                .background(SurfaceContainer),
        )
        Spacer(Modifier.height(14.dp))
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Icon(icon, contentDescription = null, tint = tone, modifier = Modifier.size(20.dp))
            Text(title, style = MaterialTheme.typography.labelLarge, color = tone, fontWeight = FontWeight.Bold)
        }
        Text(
            result.name ?: "QR tidak dikenali",
            style = MaterialTheme.typography.headlineSmall,
            modifier = Modifier.padding(top = 8.dp),
        )
        Text(
            listOfNotNull(result.nim, result.program).joinToString(" · ").ifBlank { result.message },
            color = Label,
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.padding(top = 4.dp),
        )
        if (result.message.isNotBlank() && result.nim != null) {
            Text(result.message, color = Label, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 8.dp))
        }
        Spacer(Modifier.height(18.dp))
        PrimaryButton("Lanjut scan", onDismiss, modifier = Modifier.fillMaxWidth())
    }
}

@Composable
private fun StatusChip(text: String, tone: Color, onClick: () -> Unit) {
    Pressable(onClick = onClick) { modifier ->
        Text(
            text,
            color = OnPrimary,
            modifier = modifier
                .background(tone.copy(alpha = 0.94f), RoundedCornerShape(999.dp))
                .padding(horizontal = 14.dp, vertical = 10.dp),
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Composable
private fun scanFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = Color.Transparent,
    unfocusedBorderColor = Color.Transparent,
    focusedTextColor = Ink,
    unfocusedTextColor = Ink,
    cursorColor = Ink,
    focusedContainerColor = Glass,
    unfocusedContainerColor = Glass,
    focusedPlaceholderColor = Label,
    unfocusedPlaceholderColor = Label,
)

private fun vibrateFor(context: android.content.Context, status: String) {
    val vibrator = if (Build.VERSION.SDK_INT >= 31) {
        context.getSystemService(VibratorManager::class.java)?.defaultVibrator
    } else {
        @Suppress("DEPRECATION")
        context.getSystemService(Vibrator::class.java)
    } ?: return
    if (Build.VERSION.SDK_INT >= 29) {
        val effect = when (status) {
            "accepted" -> VibrationEffect.EFFECT_CLICK
            "duplicate" -> VibrationEffect.EFFECT_HEAVY_CLICK
            else -> VibrationEffect.EFFECT_TICK
        }
        vibrator.vibrate(VibrationEffect.createPredefined(effect))
        return
    }
    val ms = if (status == "accepted") 36L else 18L
    if (Build.VERSION.SDK_INT >= 26) {
        vibrator.vibrate(VibrationEffect.createOneShot(ms, VibrationEffect.DEFAULT_AMPLITUDE))
    } else {
        @Suppress("DEPRECATION")
        vibrator.vibrate(ms)
    }
}
