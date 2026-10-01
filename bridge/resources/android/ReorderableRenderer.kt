package app.lemonfiber.native

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.gestures.detectDragGesturesAfterLongPress
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.onSizeChanged
import androidx.compose.ui.semantics.CustomAccessibilityAction
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.customActions
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import androidx.compose.ui.zIndex
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import com.nativephp.plugins.native_ui.ui.nuiDefaultFontFamily

/**
 * Draws `<native:lemonfiber-reorderable>`: rows somebody puts in order.
 *
 * A row is picked up with a long press and dragged; it trades places with each
 * neighbour it passes, and the new order is sent once it is let go. Each row
 * also offers a screen reader the two moves the template names, so the list can
 * be put in order without dragging. Where a row lands is `Reordering`'s to say.
 */
public object ReorderableRenderer {
    private val ROW = 56.dp

    /** Draws the list [node] describes, its rows in the order the screen sent. */
    @Composable
    @Suppress("LongMethod")
    public fun Render(
        node: NativeUINode,
        modifier: Modifier,
    ) {
        val p = node.props
        val keys = p.getStringList("keys")
        val names = keys.zip(p.getStringList("names")).toMap()
        val ups = keys.zip(p.getStringList("move_up")).toMap()
        val downs = keys.zip(p.getStringList("move_down")).toMap()
        val onChange = p.getCallbackId("on_change")
        val theme = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val shape = RoundedCornerShape(theme.radiusMd)

        var order by remember(node.id) { mutableStateOf(keys) }
        var lifted by remember(node.id) { mutableStateOf<String?>(null) }
        var offset by remember(node.id) { mutableFloatStateOf(0f) }
        val heights = remember(node.id) { mutableStateMapOf<String, Float>() }
        val sentByTheScreen by rememberUpdatedState(keys)

        // The screen's order wins whenever it changes, including after a send.
        LaunchedEffect(keys) { order = keys }

        val send = { next: List<String> ->
            order = next
            if (onChange != 0) {
                NativeUIBridge.sendSelectChangeEvent(onChange, node.id, Reordering.sent(next))
            }
        }

        Column(
            modifier
                .fillMaxWidth()
                .clip(shape)
                .border(1.dp, theme.outlineVariant, shape)
                .background(theme.surface),
        ) {
            order.forEachIndexed { index, row ->
                key(row) {
                    val isLifted = lifted == row
                    Row(
                        Modifier
                            .fillMaxWidth()
                            .heightIn(min = ROW)
                            .onSizeChanged { heights[row] = it.height.toFloat() }
                            .zIndex(if (isLifted) 1f else 0f)
                            .graphicsLayer { translationY = if (isLifted) offset else 0f }
                            .background(if (isLifted) theme.surfaceVariant else theme.surface)
                            .semantics(mergeDescendants = true) {
                                contentDescription = names[row].orEmpty()
                                customActions =
                                    listOfNotNull(
                                        CustomAccessibilityAction(ups[row].orEmpty()) {
                                            send(Reordering.moved(order, index, index - 1))
                                            true
                                        }.takeIf { index > 0 },
                                        CustomAccessibilityAction(downs[row].orEmpty()) {
                                            send(Reordering.moved(order, index, index + 1))
                                            true
                                        }.takeIf { index < order.lastIndex },
                                    )
                            }.pointerInput(row) {
                                detectDragGesturesAfterLongPress(
                                    onDragStart = {
                                        lifted = row
                                        offset = 0f
                                    },
                                    onDrag = { change, amount ->
                                        change.consume()
                                        offset += amount.y
                                        val from = order.indexOf(row)
                                        val height = heights[row] ?: 0f
                                        val to = Reordering.landing(from, offset, height, order.size)
                                        if (to != from) {
                                            order = Reordering.moved(order, from, to)
                                            offset -= (to - from) * height
                                        }
                                    },
                                    onDragEnd = {
                                        lifted = null
                                        offset = 0f
                                        if (order != sentByTheScreen) {
                                            send(order)
                                        }
                                    },
                                    onDragCancel = {
                                        lifted = null
                                        offset = 0f
                                        order = sentByTheScreen
                                    },
                                )
                            }.padding(horizontal = 16.dp, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            text = names[row].orEmpty(),
                            modifier = Modifier.weight(1f),
                            color = theme.onSurface,
                            fontFamily = nuiDefaultFontFamily(),
                        )
                        Icon(Icons.Filled.Menu, contentDescription = null, tint = theme.onSurfaceVariant)
                    }
                }
            }
        }
    }
}
