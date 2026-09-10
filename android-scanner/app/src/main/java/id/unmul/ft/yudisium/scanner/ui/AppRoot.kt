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
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import id.unmul.ft.yudisium.scanner.data.LocalScanResult
import id.unmul.ft.yudisium.scanner.data.local.EventEntity
import id.unmul.ft.yudisium.scanner.scan.CameraPreview
import id.unmul.ft.yudisium.scanner.ui.theme.Accent
import id.unmul.ft.yudisium.scanner.ui.theme.Bad
import id.unmul.ft.yudisium.scanner.ui.theme.Canvas
import id.unmul.ft.yudisium.scanner.ui.theme.Glass
import id.unmul.ft.yudisium.scanner.ui.theme.Good
import id.unmul.ft.yudisium.scanner.ui.theme.Ink
import id.unmul.ft.yudisium.scanner.ui.theme.Label
import id.unmul.ft.yudisium.scanner.ui.theme.Panel
import id.unmul.ft.yudisium.scanner.ui.theme.Pressable
import id.unmul.ft.yudisium.scanner.ui.theme.PrimaryButton
import id.unmul.ft.yudisium.scanner.ui.theme.Warn
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
    var email by remember { mutableStateOf(state.session.email) }
    var password by remember { mutableStateOf("") }
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Canvas)
            .statusBarsPadding()
            .imePadding()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
    ) {
        Text("Scan Yudisium", style = MaterialTheme.typography.displaySmall)
        Spacer(Modifier.height(8.dp))
        Text("Masuk dengan akun panitia. Data event diambil dari undangan resmi FT UNMUL.", color = Label)
        Spacer(Modifier.height(28.dp))
        Column(
            Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(22.dp))
                .background(Panel)
                .padding(16.dp),
        ) {
            Field(email, { email = it }, "Email", keyboardType = KeyboardType.Email)
            Spacer(Modifier.height(12.dp))
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
            Text(it, color = Bad)
        }
        Spacer(Modifier.height(20.dp))
        PrimaryButton(
            text = if (state.loading) "Masuk..." else "Masuk",
            onClick = { onLogin(email, password) },
            enabled = !state.loading,
            modifier = Modifier.fillMaxWidth(),
        )
    }
}

@Composable
private fun EventsScreen(
    state: ScanUiState,
    onRefreshEvents: () -> Unit,
    onOpenEvent: (Int) -> Unit,
    onLogout: () -> Unit,
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Canvas)
            .statusBarsPadding()
            .navigationBarsPadding()
            .padding(20.dp),
    ) {
        Text("Event", style = MaterialTheme.typography.displaySmall)
        Text(state.session.name.ifBlank { state.session.email }, color = Label)
        Spacer(Modifier.height(16.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            PrimaryButton("Perbarui", onRefreshEvents, enabled = !state.loading)
            Pressable(onClick = onLogout) { modifier ->
                Text(
                    "Keluar",
                    modifier = modifier
                        .background(Panel, RoundedCornerShape(16.dp))
                        .defaultMinSize(minWidth = 44.dp, minHeight = 52.dp)
                        .padding(horizontal = 16.dp, vertical = 14.dp),
                    color = Ink,
                )
            }
        }
        Spacer(Modifier.height(16.dp))
        state.notice?.let {
            Text(it, color = Bad)
            Spacer(Modifier.height(12.dp))
        }
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            items(state.events, key = { it.id }) { event ->
                EventCard(event, enabled = !state.loading) { onOpenEvent(event.id) }
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
                .background(Panel, RoundedCornerShape(18.dp))
                .padding(18.dp),
        ) {
            Text(if (event.isActive) "Aktif" else "Arsip", style = MaterialTheme.typography.labelLarge, color = Accent)
            Spacer(Modifier.height(4.dp))
            Text(event.name, style = MaterialTheme.typography.headlineSmall)
            Text(
                listOfNotNull(event.eventDate, event.location, "${event.participantCount} mahasiswa").joinToString(" · "),
                color = Label,
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
                .padding(16.dp),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column(
                    Modifier
                        .weight(1f)
                        .clip(RoundedCornerShape(18.dp))
                        .background(Glass)
                        .padding(horizontal = 14.dp, vertical = 10.dp),
                ) {
                    Text(state.eventName.ifBlank { "Scan" }, color = Ink, style = MaterialTheme.typography.titleMedium)
                    Text("${state.checkedIn}/${state.total} hadir", color = Label)
                }
                StatusChip(
                    text = if (state.pendingCount > 0) "${state.pendingCount} menunggu" else "Siap",
                    tone = if (state.pendingCount > 0) Warn else Good,
                    onClick = onSync,
                )
            }
            state.notice?.let {
                Spacer(Modifier.height(10.dp))
                Text(it, color = Color.White)
            }
            Spacer(Modifier.height(18.dp))
            Box(
                Modifier
                    .fillMaxWidth()
                    .weight(1f),
                contentAlignment = Alignment.Center,
            ) {
                Box(
                    Modifier
                        .size(240.dp)
                        .border(2.dp, Color.White.copy(alpha = 0.9f), RoundedCornerShape(28.dp)),
                )
            }
            OutlinedTextField(
                value = nim,
                onValueChange = { nim = it },
                placeholder = { Text("NIM manual jika QR rusak") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number, imeAction = ImeAction.Done),
                keyboardActions = KeyboardActions(onDone = {
                    if (nim.isNotBlank()) {
                        onScan(nim.trim())
                        nim = ""
                    }
                }),
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                colors = fieldColors().copy(
                    focusedTextColor = Color.White,
                    unfocusedTextColor = Color.White,
                    cursorColor = Accent,
                    focusedPlaceholderColor = Color.White.copy(alpha = 0.6f),
                    unfocusedPlaceholderColor = Color.White.copy(alpha = 0.6f),
                ),
            )
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                PrimaryButton("Ganti event", onClick = onLeaveEvent)
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
                        modifier = Modifier.align(Alignment.BottomCenter),
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
            .padding(16.dp)
            .clip(RoundedCornerShape(28.dp))
            .background(Panel.copy(alpha = 0.96f))
            .clickable(
                indication = null,
                interactionSource = remember { MutableInteractionSource() },
            ) {}
            .padding(22.dp),
    ) {
        Box(Modifier.size(10.dp).clip(CircleShape).background(tone))
        Spacer(Modifier.height(10.dp))
        Text(
            when (result.status) {
                "accepted" -> if (result.pending) "Hadir · menunggu jaringan" else "Hadir"
                "duplicate" -> "Sudah check-in"
                else -> "Tidak ditemukan"
            },
            style = MaterialTheme.typography.labelLarge,
            color = tone,
        )
        Text(result.name ?: "QR tidak dikenali", style = MaterialTheme.typography.headlineSmall)
        Text(listOfNotNull(result.nim, result.program).joinToString(" · ").ifBlank { result.message }, color = Label)
        Spacer(Modifier.height(8.dp))
        Text(result.message, color = Label)
        Spacer(Modifier.height(16.dp))
        PrimaryButton("Lanjut scan", onDismiss, modifier = Modifier.fillMaxWidth())
    }
}

@Composable
private fun StatusChip(text: String, tone: Color, onClick: () -> Unit) {
    Pressable(onClick = onClick) { modifier ->
        Text(
            text,
            color = Color.White,
            modifier = modifier
                .background(tone, RoundedCornerShape(999.dp))
                .defaultMinSize(minWidth = 44.dp, minHeight = 44.dp)
                .padding(horizontal = 12.dp, vertical = 10.dp),
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
        shape = RoundedCornerShape(16.dp),
        colors = fieldColors(),
    )
}

@Composable
private fun fieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = Accent,
    unfocusedBorderColor = Color(0xFFD1D1D6),
    focusedLabelColor = Accent,
    cursorColor = Accent,
    focusedContainerColor = Panel,
    unfocusedContainerColor = Panel,
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
