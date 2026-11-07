import Testing

@testable import LemonfiberNative

@Test("a wake hears at once where the app's runtime is up")
func aWakeHearsAtOnce() {
    #expect(ColdWake.on(runtimeIsUp: true) == .hears)
}

@Test("a wake that finds no runtime boots it before it hears")
func aColdWakeBootsTheRuntime() {
    #expect(ColdWake.on(runtimeIsUp: false) == .bootsTheRuntimeFirst)
}
