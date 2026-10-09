package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * The origin every request the player makes must stay at.
 *
 * The same cases as `DoorTests.swift`, in the same order.
 */
class DoorTest {
    private val theLocation = "https://door.home:8443/library/1/main.m3u8"

    @Test
    fun `a door is named only by an https address with a host and no credentials`() {
        assertEquals("door.home", Door.of(theLocation)?.host)
        assertEquals(8443, Door.of(theLocation)?.port)
        assertNull(Door.of("http://door.home/library/1/main.m3u8"))
        assertNull(Door.of("https://member:secret@door.home/library/1/main.m3u8"))
        assertNull(Door.of("https:///library/1/main.m3u8"))
        assertNull(Door.of("not an address"))
    }

    @Test
    fun `a door with no port named is at the https port`() {
        assertEquals(443, Door.of("https://Door.Home/library")?.port)
        assertEquals(true, Door.of("https://door.home:443/elsewhere")?.holds("https://DOOR.home/library"))
    }

    @Test
    fun `an address at another host, port or scheme is not at the door`() {
        val door = Door.of(theLocation)

        assertEquals(true, door?.holds("https://door.home:8443/library/2/main.m3u8"))
        assertEquals(false, door?.holds("https://elsewhere.example:8443/library/2/main.m3u8"))
        assertEquals(false, door?.holds("https://door.home:9443/library/2/main.m3u8"))
        assertEquals(false, door?.holds("http://door.home:8443/library/2/main.m3u8"))
        assertEquals(false, door?.holds("https://member@door.home:8443/library/2/main.m3u8"))
    }

    @Test
    fun `an address a document names is made absolute against the document`() {
        val door = Door.of(theLocation)

        assertEquals("https://door.home:8443/library/1/seg-1.ts", door?.resolve("seg-1.ts", theLocation))
        assertEquals(
            "https://door.home:8443/library/2/main.m3u8",
            door?.resolve("/library/2/main.m3u8", theLocation),
        )
        assertEquals(
            "https://door.home:8443/library/1/seg-2.ts?part=2",
            door?.resolve("seg-2.ts?part=2", theLocation),
        )
        assertEquals(
            "https://door.home:8443/library/3/a.m3u8",
            door?.resolve("https://door.home:8443/library/3/a.m3u8", theLocation),
        )
    }

    @Test
    fun `an address a document names off the door resolves to nothing`() {
        val door = Door.of(theLocation)

        assertNull(door?.resolve("https://elsewhere.example/seg-1.ts", theLocation))
        assertNull(door?.resolve("//elsewhere.example/seg-1.ts", theLocation))
        assertNull(door?.resolve("seg-1.ts", "not an address at all ::"))
    }
}
