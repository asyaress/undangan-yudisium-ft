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
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import android.provider.Settings

val Canvas = Color(0xFFF2F2F7)
val Ink = Color(0xFF1C1C1E)
val Label = Color(0xFF8E8E93)
val Accent = Color(0xFFD97706)
val Good = Color(0xFF1F8A4C)
val Warn = Color(0xFFB45309)
val Bad = Color(0xFFB42318)
val Panel = Color(0xFFFFFFFF)
val Glass = Color(0xCCF2F2F7)

private val system = FontFamily.SansSerif

private val colors = lightColorScheme(
    primary = Accent,
    onPrimary = Color.White,
    background = Canvas,
    surface = Panel,
    onBackground = Ink,
    onSurface = Ink,
)

private val type = Typography(
    displaySmall = TextStyle(
        fontFamily = system,
        fontSize = 34.sp,
        fontWeight = FontWeight.Bold,
        letterSpacing = (-0.7).sp,
        lineHeight = 38.sp,
        color = Ink,
    ),
    headlineSmall = TextStyle(
        fontFamily = system,
        fontSize = 22.sp,
        fontWeight = FontWeight.SemiBold,
        letterSpacing = (-0.4).sp,
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
    bodyLarge = TextStyle(fontFamily = system, fontSize = 17.sp, lineHeight = 24.sp, color = Ink),
    bodyMedium = TextStyle(fontFamily = system, fontSize = 15.sp, lineHeight = 21.sp, color = Ink),
    labelLarge = TextStyle(
        fontFamily = system,
        fontSize = 13.sp,
        fontWeight = FontWeight.Medium,
        letterSpacing = 0.2.sp,
        color = Label,
    ),
)

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
        targetValue = if (!reduceMotion && pressed) 0.97f else 1f,
        animationSpec = spring(dampingRatio = 1f, stiffness = 900f),
        label = "press",
    )
    content(
        modifier
            .graphicsLayer { this.scaleX = scale; this.scaleY = scale }
            .clip(RoundedCornerShape(16.dp))
            .clickable(
                interactionSource = interaction,
                indication = null,
                enabled = enabled,
                onClick = onClick,
            ),
    )
}

@Composable
fun PrimaryButton(text: String, onClick: () -> Unit, modifier: Modifier = Modifier, enabled: Boolean = true) {
    Pressable(onClick = onClick, modifier = modifier, enabled = enabled) { pressModifier ->
        Text(
            text = text,
            color = Color.White,
            style = MaterialTheme.typography.titleMedium,
            textAlign = TextAlign.Center,
            modifier = pressModifier
                .background(if (enabled) Accent else Accent.copy(alpha = 0.4f), RoundedCornerShape(16.dp))
                .defaultMinSize(minWidth = 44.dp, minHeight = 52.dp)
                .padding(PaddingValues(horizontal = 18.dp, vertical = 14.dp)),
        )
    }
}
