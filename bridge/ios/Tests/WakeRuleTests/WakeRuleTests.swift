import Testing

@testable import LemonfiberNative

// What a wake is known by and runs.
//
// The same case as `WakeRuleTest.kt`.

@Test("a wake runs the command that hears the stacks, under one name")
func aWakeRunsTheCommand() {
    #expect(WakeRule.command == "lemonfiber:hear-while-away")
    #expect(WakeRule.name == "app.lemonfiber.wake")
}
