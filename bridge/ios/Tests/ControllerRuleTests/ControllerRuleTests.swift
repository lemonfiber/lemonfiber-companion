import Testing

@testable import LemonfiberNative

// Who may connect to the player.
//
// The same cases as `ControllerRuleTest.kt`, in the same order.

private let theApp = "app.lemonfiber.companion"

@Test("this app connects to its own player")
func thisAppConnects() {
    #expect(ControllerRule.admits(asker: theApp, own: theApp, holdsMediaControl: false))
}

@Test("the system connects, by the media control permission it alone holds")
func theSystemConnects() {
    #expect(ControllerRule.admits(asker: "com.android.systemui", own: theApp, holdsMediaControl: true))
}

@Test("any other app is refused, whatever it calls itself")
func anyOtherAppIsRefused() {
    #expect(!ControllerRule.admits(asker: "com.example.listener", own: theApp, holdsMediaControl: false))
    #expect(!ControllerRule.admits(asker: theApp + ".evil", own: theApp, holdsMediaControl: false))
    #expect(!ControllerRule.admits(asker: "app.lemonfiber", own: theApp, holdsMediaControl: false))
}

@Test("an asker with no name is refused")
func aNamelessAskerIsRefused() {
    #expect(!ControllerRule.admits(asker: "", own: "", holdsMediaControl: false))
    #expect(!ControllerRule.admits(asker: "", own: theApp, holdsMediaControl: true))
}
