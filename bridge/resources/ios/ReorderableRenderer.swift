import SwiftUI

/// Draws `<native:lemonfiber-reorderable>`: rows somebody puts in order.
///
/// A row is picked up with a long press and dragged; it trades places with each
/// neighbour it passes, and the new order is sent once it is let go. Each row
/// also offers VoiceOver the two moves the template names, so the list can be
/// put in order without dragging. Where a row lands is `Reordering`'s to say,
/// the same rule `ReorderableRenderer.kt` draws by.
struct ReorderableRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme

    @State private var order: [String] = []
    @State private var lifted: String?
    @State private var offset: CGFloat = 0
    @State private var travelled: CGFloat = 0
    @State private var heights: [String: CGFloat] = [:]

    private static let row: CGFloat = 56

    var body: some View {
        let theme = themeStore.resolve(for: colorScheme)
        let keys = node.props.getStringList("keys")
        let shown = order.isEmpty ? keys : order
        let shape = RoundedRectangle(cornerRadius: theme.radiusMd)

        VStack(spacing: 0) {
            ForEach(Array(shown.enumerated()), id: \.element) { index, key in
                rowFor(key, at: index, of: shown, keys: keys, theme: theme)
            }
        }
        .background(theme.surface)
        .clipShape(shape)
        .overlay(shape.stroke(theme.outlineVariant, lineWidth: 1))
        .onAppear { order = keys }
        .onChange(of: keys) { _, sent in order = sent }
    }

    private func rowFor(
        _ key: String,
        at index: Int,
        of shown: [String],
        keys: [String],
        theme: NativeUITokens
    ) -> some View {
        let isLifted = lifted == key
        let name = part("names", of: key, in: keys)

        return HStack {
            Text(name)
                .foregroundStyle(theme.onSurface)
                .frame(maxWidth: .infinity, alignment: .leading)
            Image(systemName: "line.3.horizontal")
                .foregroundStyle(theme.onSurfaceVariant)
                .accessibilityHidden(true)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(minHeight: Self.row)
        .background(isLifted ? theme.surfaceVariant : theme.surface)
        .background(
            GeometryReader { measured in
                Color.clear
                    .onAppear { heights[key] = measured.size.height }
                    .onChange(of: measured.size.height) { _, height in heights[key] = height }
            }
        )
        .offset(y: isLifted ? offset : 0)
        .zIndex(isLifted ? 1 : 0)
        .gesture(dragging(key, keys: keys))
        .accessibilityElement(children: .ignore)
        .accessibilityLabel(name)
        .accessibilityActions {
            if index > 0 {
                Button(part("move_up", of: key, in: keys)) {
                    send(Reordering.moved(shown, from: index, to: index - 1))
                }
            }
            if index < shown.count - 1 {
                Button(part("move_down", of: key, in: keys)) {
                    send(Reordering.moved(shown, from: index, to: index + 1))
                }
            }
        }
    }

    private func dragging(_ key: String, keys: [String]) -> some Gesture {
        LongPressGesture(minimumDuration: 0.4)
            .sequenced(before: DragGesture())
            .onChanged { value in
                guard case .second(true, let drag?) = value else {
                    return
                }
                lifted = key
                let from = order.firstIndex(of: key) ?? 0
                let height = heights[key] ?? Self.row
                let to = Reordering.landing(
                    from: from,
                    offset: Double(drag.translation.height - travelled),
                    rowHeight: Double(height),
                    count: order.count
                )
                if to != from {
                    order = Reordering.moved(order, from: from, to: to)
                    travelled += CGFloat(to - from) * height
                }
                offset = drag.translation.height - travelled
            }
            .onEnded { _ in
                lifted = nil
                offset = 0
                travelled = 0
                if order != keys {
                    send(order)
                }
            }
    }

    /// One part of the row under this key, as the template sent it.
    private func part(_ named: String, of key: String, in keys: [String]) -> String {
        let parts = node.props.getStringList(named)
        guard let at = keys.firstIndex(of: key), parts.indices.contains(at) else {
            return ""
        }

        return parts[at]
    }

    private func send(_ next: [String]) {
        order = next
        let onChange = node.props.getCallbackId("on_change")
        if onChange != 0 {
            NativeElementBridge.sendSelectChangeEvent(onChange, nodeId: node.id, value: Reordering.sent(next))
        }
    }
}
