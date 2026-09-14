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
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.spring
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
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
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items as gridItems
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.CircularProgressIndicator
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import id.unmul.ft.yudisium.scanner.R
import id.unmul.ft.yudisium.scanner.data.LocalScanResult
import id.unmul.ft.yudisium.scanner.data.local.EventEntity
import id.unmul.ft.yudisium.scanner.scan.CameraPreview
import id.unmul.ft.yudisium.scanner.ui.theme.Accent
import id.unmul.ft.yudisium.scanner.ui.theme.Bad
import id.unmul.ft.yudisium.scanner.ui.theme.Canvas
import id.unmul.ft.yudisium.scanner.ui.theme.EventOrange
import id.unmul.ft.yudisium.scanner.ui.theme.Glass
import id.unmul.ft.yudisium.scanner.ui.theme.Gold
import id.unmul.ft.yudisium.scanner.ui.theme.Good
import id.unmul.ft.yudisium.scanner.ui.theme.Ink
import id.unmul.ft.yudisium.scanner.ui.theme.Label
import id.unmul.ft.yudisium.scanner.ui.theme.Panel
import id.unmul.ft.yudisium.scanner.ui.theme.Pressable
import id.unmul.ft.yudisium.scanner.ui.theme.PrimaryButton
import id.unmul.ft.yudisium.scanner.ui.theme.TextAction
import id.unmul.ft.yudisium.scanner.ui.theme.Warn
import id.unmul.ft.yudisium.scanner.ui.theme.rememberAppLayout
import id.unmul.ft.yudisium.scanner.ui.theme.rememberReduceMotion

@Composable
fun AppRoot(
    state: ScanUiState,
    onLogin: (String, String) -> Unit,
    onRefreshEvents: () -> Unit,
    onOpenEvent: (Int) -> Unit,
    onScan: (String) -> Unit,
    onSync: () -> Unit,
    onLeaveEvent: () -> Unit,
    onLogout: () -> Unit,
    onDismissResult: () -> Unit,
) {
    val session = state.session
    when {
        session.token.isBlank() -> LoginScreen(state, onLogin)
        session.periodId == 0 -> EventsScreen(state, onRefreshEvents, onOpenEvent, onLogout)
        else -> ScanScreen(state, onScan, onSync, onLeaveEvent, onDismissResult)
    }
}

@Composable
private fun LoginScreen(
    state: ScanUiState,
    onLogin: (String, String) -> Unit,
) {
    val layout = rememberAppLayout()
    var email by remember { mutableStateOf(state.session.email) }
    var password by remember { mutableStateOf("") }
    Box(
        Modifier
            .fillMaxSize()
            .background(Canvas)
            .statusBarsPadding()
            .imePadding()
            .navigationBarsPadding(),
    ) {
        Column(
            modifier = Modifier
                .align(Alignment.Center)
                .fillMaxWidth()
                .widthIn(max = 440.dp)
                .padding(horizontal = layout.pagePad)
                .verticalScroll(rememberScrollState()),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            EventBackdrop(height = layout.hero)
            Spacer(Modifier.height(16.dp))
            BrandIdentity(logo = layout.logo)
            Spacer(Modifier.height(6.dp))
            Text("Yudisium", style = MaterialTheme.typography.displaySmall)
            Text("Check-in kehadiran", color = Label, style = MaterialTheme.typography.bodyMedium)
            Spacer(Modifier.height(18.dp))
            Column(
                Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(14.dp))
                    .background(Panel),
            ) {
                Field(email, { email = it }, "Email", keyboardType = KeyboardType.Email)
                Box(Modifier.fillMaxWidth().height(0.5.dp).padding(start = 16.dp).background(Color(0x147C7C80)))
                Field(
                    password,
                    { password = it },
                    "Password",
                    password = true,
                    imeAction = ImeAction.Done,
                    onDone = { onLogin(email, password) },
                )
            }
            state.notice?.let {
                Spacer(Modifier.height(12.dp))
                Text(it, color = Bad, style = MaterialTheme.typography.bodyMedium)
            }
            Spacer(Modifier.height(16.dp))
            PrimaryButton(
                text = if (state.loading) "Masuk..." else "Masuk",
                onClick = { onLogin(email, password) },
                enabled = !state.loading,
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }
}

@Composable
private fun EventsScreen(
    state: ScanUiState,
    onRefreshEvents: () -> Unit,
    onOpenEvent: (Int) -> Unit,
    onLogout: () -> Unit,
) {
    val layout = rememberAppLayout()
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Canvas)
            .statusBarsPadding()
            .navigationBarsPadding()
            .padding(horizontal = layout.pagePad),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Column(
            Modifier
                .fillMaxHeight()
                .widthIn(max = layout.contentMax)
                .fillMaxWidth(),
        ) {
            Spacer(Modifier.height(if (layout.landscape) 8.dp else 12.dp))
            EventBackdrop(height = layout.hero)
            Spacer(Modifier.height(16.dp))
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("Pilih event", style = MaterialTheme.typography.displaySmall)
                    Text(
                        "Universitas Mulawarman",
                        color = Accent,
                        style = MaterialTheme.typography.labelLarge,
                    )
                    Text(
                        state.session.name.ifBlank { state.session.email },
                        color = Label,
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
                TextAction("Perbarui", onRefreshEvents, enabled = !state.loading)
                TextAction("Keluar", onLogout, color = Bad)
            }
            Spacer(Modifier.height(12.dp))
            state.notice?.let {
                Text(it, color = Bad, style = MaterialTheme.typography.bodyMedium)
                Spacer(Modifier.height(10.dp))
            }
            if (layout.columns == 1) {
                LazyColumn(
                    modifier = Modifier.weight(1f).fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    items(state.events, key = { it.id }) { event ->
                        EventCard(event, enabled = !state.loading) { onOpenEvent(event.id) }
                    }
                }
            } else {
                LazyVerticalGrid(
                    columns = GridCells.Fixed(2),
                    modifier = Modifier.weight(1f).fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    gridItems(state.events, key = { it.id }) { event ->
                        EventCard(event, enabled = !state.loading) { onOpenEvent(event.id) }
                    }
                }
            }
        }
    }
}

@Composable
private fun EventCard(event: EventEntity, enabled: Boolean, onClick: () -> Unit) {
    Pressable(onClick = onClick, enabled = enabled) { modifier ->
        Column(
            modifier
                .fillMaxWidth()
                .background(Panel, RoundedCornerShape(14.dp))
                .padding(horizontal = 16.dp, vertical = 14.dp),
        ) {
            Text(
                if (event.isActive) "Aktif" else "Arsip",
                style = MaterialTheme.typography.labelLarge,
                color = if (event.isActive) EventOrange else Label,
            )
            Spacer(Modifier.height(4.dp))
            Text(event.name, style = MaterialTheme.typography.titleMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Spacer(Modifier.height(4.dp))
            Text(
                listOfNotNull(event.eventDate, event.location, "${event.participantCount} mahasiswa").joinToString(" · "),
                color = Label,
                style = MaterialTheme.typography.bodyMedium,
            )
        }
    }
}

@Composable
private fun EventBackdrop(height: Dp, modifier: Modifier = Modifier) {
    Box(
        modifier
            .fillMaxWidth()
            .height(height)
            .clip(RoundedCornerShape(18.dp)),
    ) {
        Image(
            painter = painterResource(R.drawable.backdrop_yudisium),
            contentDescription = "Yudisium Fakultas Teknik Universitas Mulawarman",
            modifier = Modifier.fillMaxSize(),
            contentScale = ContentScale.Crop,
            alignment = Alignment.Center,
        )
    }
}

@Composable
private fun BrandIdentity(logo: Dp) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Image(
            painter = painterResource(R.drawable.logo_unmul),
            contentDescription = "Lambang Universitas Mulawarman",
            modifier = Modifier.size(logo),
            contentScale = ContentScale.Fit,
        )
        Column {
            Text("Universitas Mulawarman", style = MaterialTheme.typography.titleMedium)
            Text("Fakultas Teknik", color = Accent, style = MaterialTheme.typography.labelLarge)
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
            CameraPreview(
                enabled = true,
                modifier = Modifier.fillMaxSize(),
                onBarcode = onScan,
            )
        }
        Column(
            Modifier
                .fillMaxSize()
                .statusBarsPadding()
                .navigationBarsPadding()
                .imePadding()
                .padding(chromePad),
        ) {
            Row(
                Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
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
                StatusChip(
                    text = if (state.pendingCount > 0) "${state.pendingCount}" else "Siap",
                    tone = if (state.pendingCount > 0) Warn else Ink,
                    onClick = onSync,
                )
            }
            state.notice?.let {
                Spacer(Modifier.height(8.dp))
                Text(it, color = Color.White, style = MaterialTheme.typography.bodyMedium)
            }
            Box(
                Modifier
                    .fillMaxWidth()
                    .weight(1f),
                contentAlignment = Alignment.Center,
            ) {
                Box(
                    Modifier
                        .size(layout.frame)
                        .border(1.5.dp, Gold.copy(alpha = 0.92f), RoundedCornerShape(28.dp)),
                )
            }
            if (layout.landscape) {
                Row(
                    Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    NimField(
                        nim = nim,
                        onNimChange = { nim = it },
                        onSubmit = {
                            if (nim.isNotBlank()) {
                                onScan(nim.trim())
                                nim = ""
                            }
                        },
                        modifier = Modifier.weight(1f),
                    )
                    TextAction("‹ Event", onLeaveEvent, color = Color.White.copy(alpha = 0.92f))
                }
            } else {
                NimField(
                    nim = nim,
                    onNimChange = { nim = it },
                    onSubmit = {
                        if (nim.isNotBlank()) {
                            onScan(nim.trim())
                            nim = ""
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                )
                TextAction("‹ Event", onLeaveEvent, color = Color.White.copy(alpha = 0.92f))
            }
        }

        AnimatedVisibility(
            visible = state.result != null,
            enter = if (reduceMotion) {
                fadeIn()
            } else {
                fadeIn(spring(dampingRatio = 1f, stiffness = Spring.StiffnessMedium)) +
                    slideInVertically(spring(dampingRatio = 1f, stiffness = 380f)) { it }
            },
            exit = if (reduceMotion) {
                fadeOut()
            } else {
                fadeOut(spring(dampingRatio = 1f, stiffness = Spring.StiffnessMedium)) +
                    slideOutVertically(spring(dampingRatio = 1f, stiffness = 380f)) { it }
            },
            modifier = Modifier.fillMaxSize(),
        ) {
            state.result?.let { result ->
                Box(Modifier.fillMaxSize()) {
                    Box(
                        Modifier
                            .fillMaxSize()
                            .background(Color.Black.copy(alpha = 0.28f))
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
                color = Accent,
            )
        }
    }
}

@Composable
private fun ResultSheet(result: LocalScanResult, onDismiss: () -> Unit, modifier: Modifier = Modifier) {
    val tone = when (result.status) {
        "accepted" -> Good
        "duplicate" -> Warn
        else -> Bad
    }
    Column(
        modifier
            .fillMaxWidth()
            .navigationBarsPadding()
            .padding(horizontal = 16.dp, vertical = 12.dp)
            .clip(RoundedCornerShape(20.dp))
            .background(Panel)
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
            ) {}
            .padding(horizontal = 20.dp, vertical = 14.dp),
    ) {
        Box(
            Modifier
                .align(Alignment.CenterHorizontally)
                .size(width = 36.dp, height = 5.dp)
                .clip(RoundedCornerShape(99.dp))
                .background(Color(0x337C7C80)),
        )
        Spacer(Modifier.height(16.dp))
        Text(
            when (result.status) {
                "accepted" -> if (result.pending) "Hadir · menunggu" else "Hadir"
                "duplicate" -> "Sudah check-in"
                else -> "Tidak ditemukan"
            },
            style = MaterialTheme.typography.labelLarge,
            color = tone,
        )
        Text(result.name ?: "QR tidak dikenali", style = MaterialTheme.typography.headlineSmall)
        Text(
            listOfNotNull(result.nim, result.program).joinToString(" · ").ifBlank { result.message },
            color = Label,
            style = MaterialTheme.typography.bodyMedium,
        )
        Spacer(Modifier.height(18.dp))
        PrimaryButton("Lanjut", onDismiss, modifier = Modifier.fillMaxWidth())
    }
}

@Composable
private fun NimField(
    nim: String,
    onNimChange: (String) -> Unit,
    onSubmit: () -> Unit,
    modifier: Modifier = Modifier,
) {
    OutlinedTextField(
        value = nim,
        onValueChange = onNimChange,
        placeholder = { Text("NIM jika QR rusak") },
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number, imeAction = ImeAction.Done),
        keyboardActions = KeyboardActions(onDone = { onSubmit() }),
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        colors = scanFieldColors(),
    )
}

@Composable
private fun StatusChip(text: String, tone: Color, onClick: () -> Unit) {
    Pressable(onClick = onClick) { modifier ->
        Text(
            text,
            color = Color.White,
            modifier = modifier
                .background(tone.copy(alpha = 0.92f), RoundedCornerShape(999.dp))
                .defaultMinSize(minWidth = 44.dp, minHeight = 44.dp)
                .padding(horizontal = 14.dp, vertical = 10.dp),
        )
    }
}

@Composable
private fun Field(
    value: String,
    onChange: (String) -> Unit,
    label: String,
    placeholder: String? = null,
    password: Boolean = false,
    keyboardType: KeyboardType = KeyboardType.Text,
    imeAction: ImeAction = ImeAction.Next,
    onDone: (() -> Unit)? = null,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onChange,
        label = { Text(label) },
        placeholder = placeholder?.let { { Text(it) } },
        singleLine = true,
        visualTransformation = if (password) PasswordVisualTransformation() else androidx.compose.ui.text.input.VisualTransformation.None,
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType, imeAction = imeAction),
        keyboardActions = KeyboardActions(onDone = { onDone?.invoke() }),
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(0.dp),
        colors = fieldColors(),
    )
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

@Composable
private fun fieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = Color.Transparent,
    unfocusedBorderColor = Color.Transparent,
    focusedLabelColor = Label,
    unfocusedLabelColor = Label,
    cursorColor = Ink,
    focusedContainerColor = Color.Transparent,
    unfocusedContainerColor = Color.Transparent,
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
