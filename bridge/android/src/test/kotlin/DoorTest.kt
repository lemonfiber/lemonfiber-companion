package app.lemonfiber.native

import java.net.URI
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

    @Test
    fun `an address with a name before the host is not at the door wherever the at sign falls`() {
        val door = Door.of(theLocation)

        assertEquals(false, door?.holds("https://door.home:8443@elsewhere.example/library"))
        assertEquals(false, door?.holds("https://elsewhere.example@door.home:8443/library"))
        assertEquals(false, door?.holds("https://door.home%40elsewhere.example:8443/library"))
        assertNull(Door.of("https://elsewhere.example@door.home/library"))
    }

    @Test
    fun `a host written with escapes, other scripts or a stray dot or hyphen names no door`() {
        assertNull(Door.of("https://do%6Fr.home/library"))
        assertNull(Door.of("https://dóor.home/library"))
        assertNull(Door.of("https://door.home./library"))
        assertNull(Door.of("https://door..home/library"))
        assertNull(Door.of("https://-door.home/library"))
        assertNull(Door.of("https://door-.home/library"))
        assertNull(Door.of("https://door_1.home/library"))
        assertNull(Door.of("https://[::1]:8443/library"))
    }

    @Test
    fun `an address with a backslash, a space or a control character names no door`() {
        assertNull(Door.of("https://door.home\\@elsewhere.example/library"))
        assertNull(Door.of("https://door.home\\elsewhere.example/library"))
        assertNull(Door.of("https://door.home/a library"))
        assertNull(Door.of("https://door.home/library\t"))
        assertNull(Door.of("https://door.home/library\u007F"))
    }

    @Test
    fun `a port is read only as the plain number a connection is made to`() {
        assertNull(Door.of("https://door.home:/library"))
        assertNull(Door.of("https://door.home:0443/library"))
        assertNull(Door.of("https://door.home:0/library"))
        assertNull(Door.of("https://door.home:65536/library"))
        assertNull(Door.of("https://door.home:123456/library"))
        assertNull(Door.of("https://door.home:84a3/library"))
        assertNull(Door.of("https://door.home:8443:1/library"))
        assertEquals(65_535, Door.of("https://door.home:65535/library")?.port)
        assertEquals("192.168.1.10", Door.of("https://192.168.1.10:8096/library")?.host)
    }

    @Test
    fun `the address handed to the platform is the one that was checked`() {
        val door = Door.of(theLocation)
        val admitted = door?.admitted("HTTPS://Door.Home:8443/library/2/main.m3u8?part=1")

        assertEquals("door.home", admitted?.host?.lowercase())
        assertEquals(8443, admitted?.port)
        assertEquals("HTTPS://Door.Home:8443/library/2/main.m3u8?part=1", admitted?.toString())
        assertNull(door?.admitted("https://door.home:9443/library"))
        assertNull(door?.admitted("https:door.home:8443/library"))
        assertNull(door?.admitted("https:/door.home:8443/library"))
    }

    @Test
    fun `the grammar reads only an https authority of a plain host and number`() {
        assertEquals("door.home", Door.authority("https://Door.Home:8443/library")?.first)
        assertEquals(8443, Door.authority("https://Door.Home:8443/library")?.second)
        assertNull(Door.authority("https://door.home/library")?.second)
        assertEquals("door.home", Door.authority("https://door.home/library")?.first)
        assertNull(Door.authority("http://door.home/library"))
        assertNull(Door.authority("https:door.home/library"))
        assertNull(Door.authority("https://door.home/library\\a"))
        assertNull(Door.authority("https://door.home:8443:1/library"))
        assertNull(Door.authority("https://door.home:+443/library"))
        assertNull(Door.authority("https://door.home/a library"))
        assertNull(Door.authority("https://-door.home/library"))
        assertNull(Door.authority("https://door-.home/library"))
        assertNull(Door.authority("https://door_1.home/library"))
    }

    @Test
    fun `the platform's reading must agree with the grammar's on scheme, name, host and port`() {
        val written = Pair("door.home", 8443)

        assertEquals(true, Door.agrees(URI("https://door.home:8443/library"), written))
        assertEquals(false, Door.agrees(URI("http://door.home:8443/library"), written))
        assertEquals(false, Door.agrees(URI("https://member@door.home:8443/library"), written))
        assertEquals(false, Door.agrees(URI("https://:secret@door.home:8443/library"), written))
        assertEquals(false, Door.agrees(URI("https://elsewhere.example:8443/library"), written))
        assertEquals(false, Door.agrees(URI("https://door.home/library"), written))
    }
}
