package id.unmul.ft.yudisium.scanner.ui

import androidx.compose.animation.core.LinearEasing
import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxScope
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Login
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Dns
import androidx.compose.material.icons.filled.Error
import androidx.compose.material.icons.filled.Shield
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.min
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import id.unmul.ft.yudisium.scanner.R
import id.unmul.ft.yudisium.scanner.ui.theme.Accent
import id.unmul.ft.yudisium.scanner.ui.theme.ErrorContainer
import id.unmul.ft.yudisium.scanner.ui.theme.EventOrange
import id.unmul.ft.yudisium.scanner.ui.theme.Ink
import id.unmul.ft.yudisium.scanner.ui.theme.InverseOnSurface
import id.unmul.ft.yudisium.scanner.ui.theme.InverseSurface
import id.unmul.ft.yudisium.scanner.ui.theme.Label
import id.unmul.ft.yudisium.scanner.ui.theme.LabelVariant
import id.unmul.ft.yudisium.scanner.ui.theme.OnErrorContainer
import id.unmul.ft.yudisium.scanner.ui.theme.OnPrimary
import id.unmul.ft.yudisium.scanner.ui.theme.Panel
import id.unmul.ft.yudisium.scanner.ui.theme.Pressable
import id.unmul.ft.yudisium.scanner.ui.theme.PrimaryContainer
import id.unmul.ft.yudisium.scanner.ui.theme.SurfaceContainer
import id.unmul.ft.yudisium.scanner.ui.theme.SurfaceContainerLow

@Composable
fun HeroBanner(height: Dp, modifier: Modifier = Modifier) {
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
        Box(
            Modifier
                .fillMaxSize()
                .background(
                    Brush.verticalGradient(
                        0f to Color.Transparent,
                        1f to InverseSurface.copy(alpha = 0.55f),
                    ),
                ),
        )
    }
}

@Composable
fun BrandIdentity(logo: Dp) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Box(
            Modifier
                .size(logo)
                .clip(RoundedCornerShape(12.dp))
                .background(Panel),
            contentAlignment = Alignment.Center,
        ) {
            Image(
                painter = painterResource(R.drawable.logo_unmul),
                contentDescription = "Lambang Universitas Mulawarman",
                modifier = Modifier.size(logo - 6.dp),
                contentScale = ContentScale.Fit,
            )
        }
        Column(Modifier.weight(1f, fill = false)) {
            Text(
                "Universitas Mulawarman",
                style = MaterialTheme.typography.titleMedium,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                "Fakultas Teknik",
                color = Label,
                style = MaterialTheme.typography.labelLarge,
            )
        }
    }
}

@Composable
fun ServerHostChip(host: String) {
    Row(
        Modifier
            .clip(RoundedCornerShape(999.dp))
            .background(SurfaceContainer)
            .padding(horizontal = 10.dp, vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Icon(Icons.Default.Dns, contentDescription = null, tint = Label, modifier = Modifier.size(14.dp))
        Text(host, style = MaterialTheme.typography.labelLarge, color = LabelVariant)
    }
}

@Composable
fun ErrorBanner(message: String, onDismiss: () -> Unit) {
    Row(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(12.dp))
            .background(ErrorContainer)
            .padding(12.dp),
        horizontalArrangement = Arrangement.spacedBy(10.dp),
        verticalAlignment = Alignment.Top,
    ) {
        Icon(
            Icons.Default.Error,
            contentDescription = null,
            tint = OnErrorContainer,
            modifier = Modifier.size(20.dp),
        )
        Text(
            message,
            Modifier.weight(1f),
            style = MaterialTheme.typography.labelLarge,
            color = OnErrorContainer,
        )
        IconButton(onClick = onDismiss, modifier = Modifier.size(28.dp)) {
            Icon(Icons.Default.Close, contentDescription = "Tutup pesan", tint = OnErrorContainer.copy(alpha = 0.7f))
        }
    }
}

@Composable
fun GroupedCredentialCard(
    email: String,
    onEmailChange: (String) -> Unit,
    password: String,
    onPasswordChange: (String) -> Unit,
    passwordVisible: Boolean,
    onTogglePassword: () -> Unit,
    onSubmit: () -> Unit,
) {
    Column(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(Panel),
    ) {
        CredentialRow(
            label = "Email panitia",
            value = email,
            onValueChange = onEmailChange,
            placeholder = "panitia@ft.unmul.ac.id",
            visualTransformation = VisualTransformation.None,
            trailing = null,
        )
        HorizontalDivider(color = SurfaceContainer, thickness = 1.dp)
        CredentialRow(
            label = "Kata sandi",
            value = password,
            onValueChange = onPasswordChange,
            placeholder = "••••••••••••",
            visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
            trailing = {
                IconButton(onClick = onTogglePassword) {
                    Icon(
                        if (passwordVisible) Icons.Default.VisibilityOff else Icons.Default.Visibility,
                        contentDescription = "Tampilkan sandi",
                        tint = Label,
                    )
                }
            },
            onDone = onSubmit,
        )
    }
}

@Composable
private fun CredentialRow(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
    placeholder: String,
    visualTransformation: VisualTransformation,
    trailing: (@Composable () -> Unit)?,
    onDone: (() -> Unit)? = null,
) {
    Row(
        Modifier
            .fillMaxWidth()
            .padding(horizontal = 14.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Column(Modifier.weight(1f)) {
            Text(label.uppercase(), style = MaterialTheme.typography.labelSmall, color = Label)
            OutlinedTextField(
                value = value,
                onValueChange = onValueChange,
                placeholder = { Text(placeholder, color = Label.copy(alpha = 0.7f)) },
                singleLine = true,
                visualTransformation = visualTransformation,
                modifier = Modifier.fillMaxWidth(),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = Color.Transparent,
                    unfocusedBorderColor = Color.Transparent,
                    focusedContainerColor = Color.Transparent,
                    unfocusedContainerColor = Color.Transparent,
                    cursorColor = Ink,
                ),
            )
        }
        trailing?.invoke()
    }
}

@Composable
fun LoginPrimaryButton(loading: Boolean, enabled: Boolean, onClick: () -> Unit) {
    Pressable(
        onClick = onClick,
        enabled = enabled && !loading,
        modifier = Modifier.fillMaxWidth(),
    ) { modifier ->
        Row(
            modifier
                .fillMaxWidth()
                .height(50.dp)
                .background(
                    if (enabled && !loading) PrimaryContainer else PrimaryContainer.copy(alpha = 0.4f),
                    RoundedCornerShape(14.dp),
                ),
            horizontalArrangement = Arrangement.Center,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            if (loading) {
                CircularProgressIndicator(Modifier.size(20.dp), color = OnPrimary, strokeWidth = 2.dp)
                Spacer(Modifier.width(8.dp))
                Text("Memverifikasi...", color = OnPrimary, style = MaterialTheme.typography.titleMedium)
            } else {
                Icon(Icons.AutoMirrored.Filled.Login, contentDescription = null, tint = OnPrimary, modifier = Modifier.size(20.dp))
                Spacer(Modifier.width(8.dp))
                Text("Masuk", color = OnPrimary, style = MaterialTheme.typography.titleMedium)
            }
        }
    }
}

@Composable
fun OperationalFooterNote() {
    Row(
        Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(12.dp))
            .background(SurfaceContainerLow)
            .padding(horizontal = 12.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Icon(Icons.Default.Shield, contentDescription = null, tint = Label, modifier = Modifier.size(16.dp))
        Text(
            "Bukan untuk mahasiswa. Khusus petugas registrasi hari H.",
            style = MaterialTheme.typography.labelSmall,
            color = Label,
        )
    }
}

@Composable
fun ScanFocusRegion(
    modifier: Modifier = Modifier,
    widthFraction: Float = 0.96f,
    heightFraction: Float = 0.92f,
    content: @Composable BoxScope.(frameSize: Dp) -> Unit,
) {
    BoxWithConstraints(modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        val side = min(maxWidth * widthFraction, maxHeight * heightFraction)
        val density = LocalDensity.current
        val sidePx = with(density) { side.toPx() }
        val left = (constraints.maxWidth - sidePx) / 2f
        val top = (constraints.maxHeight - sidePx) / 2f
        Canvas(Modifier.fillMaxSize()) {
            val scrim = Color.Black.copy(alpha = 0.52f)
            drawRect(scrim, topLeft = Offset.Zero, size = Size(size.width, top))
            drawRect(scrim, topLeft = Offset(0f, top + sidePx), size = Size(size.width, size.height - top - sidePx))
            drawRect(scrim, topLeft = Offset(0f, top), size = Size(left, sidePx))
            drawRect(scrim, topLeft = Offset(left + sidePx, top), size = Size(size.width - left - sidePx, sidePx))
        }
        Box(Modifier.size(side), contentAlignment = Alignment.Center) {
            content(side)
        }
    }
}

@Composable
fun ScanViewfinder(frameSize: Dp, modifier: Modifier = Modifier) {
    val transition = rememberInfiniteTransition(label = "scan-line")
    val lineOffset by transition.animateFloat(
        initialValue = 0.08f,
        targetValue = 0.92f,
        animationSpec = infiniteRepeatable(
            animation = tween(2400, easing = LinearEasing),
            repeatMode = RepeatMode.Reverse,
        ),
        label = "line",
    )
    val cornerArm = frameSize * 0.16f
    val stroke = 5.dp
    Box(modifier.size(frameSize), contentAlignment = Alignment.Center) {
        Canvas(Modifier.fillMaxSize()) {
            val arm = cornerArm.toPx()
            val s = stroke.toPx()
            val color = EventOrange
            val cap = StrokeCap.Round
            // top-left
            drawLine(color, Offset(arm, 0f), Offset(0f, 0f), s, cap)
            drawLine(color, Offset(0f, 0f), Offset(0f, arm), s, cap)
            // top-right
            drawLine(color, Offset(size.width - arm, 0f), Offset(size.width, 0f), s, cap)
            drawLine(color, Offset(size.width, 0f), Offset(size.width, arm), s, cap)
            // bottom-left
            drawLine(color, Offset(0f, size.height - arm), Offset(0f, size.height), s, cap)
            drawLine(color, Offset(0f, size.height), Offset(arm, size.height), s, cap)
            // bottom-right
            drawLine(color, Offset(size.width - arm, size.height), Offset(size.width, size.height), s, cap)
            drawLine(color, Offset(size.width, size.height - arm), Offset(size.width, size.height), s, cap)
            val y = size.height * lineOffset
            drawLine(
                color = EventOrange.copy(alpha = 0.75f),
                start = Offset(size.width * 0.06f, y),
                end = Offset(size.width * 0.94f, y),
                strokeWidth = 2.5f,
                cap = StrokeCap.Round,
            )
        }
        Text(
            "Arahkan QR ke kotak",
            Modifier
                .align(Alignment.BottomCenter)
                .padding(bottom = 10.dp)
                .clip(RoundedCornerShape(999.dp))
                .background(Color.Black.copy(alpha = 0.45f))
                .padding(horizontal = 12.dp, vertical = 6.dp),
            style = MaterialTheme.typography.labelLarge,
            color = InverseOnSurface,
        )
    }
}
