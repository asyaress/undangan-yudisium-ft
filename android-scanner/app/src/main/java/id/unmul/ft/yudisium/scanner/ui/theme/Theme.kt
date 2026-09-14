package id.unmul.ft.yudisium.scanner.ui.theme

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.Typography
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import android.provider.Settings
import kotlin.math.min

val Canvas = Color(0xFFF5F5F7)
val Ink = Color(0xFF1C1C1E)
val Label = Color(0xFF8E8E93)
val LabelVariant = Color(0xFF636366)
val Accent = Color(0xFF1F7A3A)
val PrimaryContainer = Color(0xFF1F7A3A)
val OnPrimary = Color.White
/** Aksen oranye FT — viewfinder, status aktif, antrian sync (bukan emas). */
val EventOrange = Color(0xFFE85D04)
val Good = Color(0xFF1F7A3A)
val Warn = EventOrange
val Bad = Color(0xFFB42318)
val ErrorContainer = Color(0xFFFEE2E2)
val OnErrorContainer = Color(0xFF991B1B)
val Panel = Color(0xFFFFFFFF)
val SurfaceContainer = Color(0xFFEAEAEC)
val SurfaceContainerLow = Color(0xFFF0F0F2)
val Glass = Color(0xF5FFFFFF)
val InverseSurface = Color(0xFF2C2C2E)
val InverseOnSurface = Color(0xFFF2F2F7)

private val system = FontFamily.SansSerif

private val colors = lightColorScheme(
    primary = Accent,
    onPrimary = OnPrimary,
    primaryContainer = PrimaryContainer,
    onPrimaryContainer = OnPrimary,
    secondary = EventOrange,
    secondaryContainer = EventOrange,
    error = Bad,
    errorContainer = ErrorContainer,
    onErrorContainer = OnErrorContainer,
    background = Canvas,
    surface = Panel,
    surfaceContainer = SurfaceContainer,
    surfaceContainerLow = SurfaceContainerLow,
    onBackground = Ink,
    onSurface = Ink,
    onSurfaceVariant = Label,
    outline = Label,
)

private val type = Typography(
    displaySmall = TextStyle(
        fontFamily = system,
        fontSize = 34.sp,
        fontWeight = FontWeight.Bold,
        letterSpacing = (-0.7).sp,
        lineHeight = 41.sp,
        color = Ink,
    ),
    headlineSmall = TextStyle(
        fontFamily = system,
        fontSize = 22.sp,
        fontWeight = FontWeight.SemiBold,
        letterSpacing = (-0.33).sp,
        lineHeight = 28.sp,
        color = Ink,
    ),
    titleMedium = TextStyle(
        fontFamily = system,
        fontSize = 17.sp,
        fontWeight = FontWeight.SemiBold,
        letterSpacing = (-0.2).sp,
        lineHeight = 22.sp,
        color = Ink,
    ),
    bodyLarge = TextStyle(fontFamily = system, fontSize = 17.sp, lineHeight = 22.sp, color = Ink),
    bodyMedium = TextStyle(fontFamily = system, fontSize = 15.sp, lineHeight = 20.sp, color = Ink),
    labelLarge = TextStyle(
        fontFamily = system,
        fontSize = 13.sp,
        fontWeight = FontWeight.Medium,
        letterSpacing = 0.2.sp,
        color = Label,
    ),
    labelSmall = TextStyle(
        fontFamily = system,
        fontSize = 11.sp,
        fontWeight = FontWeight.Medium,
        letterSpacing = 0.22.sp,
        color = Label,
    ),
)

data class AppLayout(
    val compact: Boolean,
    val landscape: Boolean,
    val pagePad: Dp,
    val contentMax: Dp,
    val logo: Dp,
    val hero: Dp,
    val frame: Dp,
    val columns: Int,
)

@Composable
fun rememberAppLayout(): AppLayout {
    val config = LocalConfiguration.current
    val width = config.screenWidthDp
    val height = config.screenHeightDp
    val compact = width < 600
    val landscape = width > height
    val shortest = min(width, height)
    return AppLayout(
        compact = compact,
        landscape = landscape,
        pagePad = if (compact) 16.dp else 24.dp,
        contentMax = if (compact) 440.dp else 560.dp,
        logo = if (compact) 44.dp else 52.dp,
        hero = when {
            landscape -> 108.dp
            compact -> 156.dp
            else -> 172.dp
        },
        frame = (if (landscape) min(height * 0.52f, 300f) else min(shortest * 0.62f, 270f)).dp,
        columns = if (width >= 840) 2 else 1,
    )
}

@Composable
fun ScannerTheme(content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = colors, typography = type, content = content)
}

@Composable
fun rememberReduceMotion(): Boolean {
    val context = LocalContext.current
    return remember {
        Settings.Global.getFloat(
            context.contentResolver,
            Settings.Global.TRANSITION_ANIMATION_SCALE,
            1f,
        ) == 0f
    }
}

@Composable
fun Pressable(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    content: @Composable (Modifier) -> Unit,
) {
    val interaction = remember { MutableInteractionSource() }
    val reduceMotion = rememberReduceMotion()
    val pressed by interaction.collectIsPressedAsState()
    val scale by animateFloatAsState(
        targetValue = if (!reduceMotion && pressed && enabled) 0.97f else 1f,
        animationSpec = spring(dampingRatio = 1f, stiffness = 900f),
        label = "press",
    )
    content(
        modifier
            .graphicsLayer { scaleX = scale; scaleY = scale }
            .clip(RoundedCornerShape(14.dp))
            .clickable(
                interactionSource = interaction,
                indication = null,
                enabled = enabled,
                onClick = onClick,
            ),
    )
}

@Composable
fun PrimaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    leading: (@Composable () -> Unit)? = null,
) {
    Pressable(onClick = onClick, modifier = modifier, enabled = enabled) { pressModifier ->
        Text(
            text = text,
            color = OnPrimary,
            style = MaterialTheme.typography.titleMedium,
            textAlign = TextAlign.Center,
            modifier = pressModifier
                .background(
                    if (enabled) PrimaryContainer else PrimaryContainer.copy(alpha = 0.35f),
                    RoundedCornerShape(14.dp),
                )
                .defaultMinSize(minWidth = 44.dp, minHeight = 50.dp)
                .padding(PaddingValues(horizontal = 16.dp, vertical = 13.dp)),
        )
    }
}

@Composable
fun TextAction(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    color: Color = Accent,
    enabled: Boolean = true,
) {
    Pressable(onClick = onClick, modifier = modifier, enabled = enabled) { pressModifier ->
        Text(
            text = text,
            color = if (enabled) color else color.copy(alpha = 0.4f),
            style = MaterialTheme.typography.titleMedium,
            modifier = pressModifier
                .defaultMinSize(minWidth = 44.dp, minHeight = 44.dp)
                .padding(horizontal = 8.dp, vertical = 10.dp),
        )
    }
}
