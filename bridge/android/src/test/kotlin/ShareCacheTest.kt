package app.lemonfiber.native

import java.io.File
import java.nio.file.Files
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertContentEquals
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Where a handed-over file is written, and how little of it is left behind.
 *
 * Run against a temporary directory standing in for the app's own cache. The
 * directory the cache owns sits inside it, so a test can see both what the
 * cache wrote and that it wrote nowhere else.
 *
 * The same cases as `ShareCacheTests.swift`, in the same order.
 */
class ShareCacheTest {
    private val caches: File = Files.createTempDirectory("caches").toFile()

    private val directory: File = File(caches, ShareCache.DIRECTORY)

    private val cache: ShareCache = ShareCache.inside(caches)

    @AfterTest
    fun tidy() {
        caches.deleteRecursively()
    }

    @Test
    fun `a file is written under the name given, byte for byte, in the directory named for handing over`() {
        val written = assertNotNull(cache.write("a-bundle.tar.gz", byteArrayOf(1, 2, 3)))

        assertEquals("lemonfiber-handover", ShareCache.DIRECTORY)
        assertEquals(File(directory, "a-bundle.tar.gz").canonicalFile, written.canonicalFile)
        assertContentEquals(byteArrayOf(1, 2, 3), written.readBytes())
    }

    @Test
    fun `a second handover sweeps the first, so one file at most is ever held`() {
        val first = assertNotNull(cache.write("the-first.tar.gz", byteArrayOf(1)))
        cache.write("the-second.tar.gz", byteArrayOf(2))

        assertFalse(first.exists())
        assertEquals(listOf("the-second.tar.gz"), directory.list()?.toList())
    }

    @Test
    fun `sweeping takes the directory and what is in it away, and touches nothing beside it`() {
        val beside = File(caches, "somebody-elses.txt").apply { writeText("kept") }
        cache.write("a-bundle.tar.gz", byteArrayOf(1))

        cache.sweep()
        cache.sweep()

        assertFalse(directory.exists())
        assertEquals("kept", beside.readText())
    }

    @Test
    fun `a name that is nothing or a way out is refused, and the last file is still swept`() {
        val before = assertNotNull(cache.write("a-bundle.tar.gz", byteArrayOf(1)))

        for (name in listOf("", ".", "..", "../escaped", "a/b", "a\\b", "a\u0000b")) {
            assertNull(cache.write(name, byteArrayOf(1)), name)
            assertFalse(ShareCache.isAFileName(name), name)
        }

        assertFalse(before.exists())
        assertFalse(File(caches, "escaped").exists())
    }

    @Test
    fun `nothing to write is refused, and the last file is still swept`() {
        val before = assertNotNull(cache.write("a-bundle.tar.gz", byteArrayOf(1)))

        assertNull(cache.write("a-bundle.tar.gz", byteArrayOf()))
        assertFalse(before.exists())
    }

    @Test
    fun `a directory that cannot be made answers nothing rather than failing`() {
        val aFile = File(caches, "a-file").apply { writeText("in the way") }

        assertNull(ShareCache(File(aFile, ShareCache.DIRECTORY)).write("a-bundle.tar.gz", byteArrayOf(1)))
    }

    @Test
    fun `the only file a grant is ever for is the one the cache holds`() {
        val written = assertNotNull(cache.write("a-bundle.tar.gz", byteArrayOf(1)))
        val beside = File(caches, "a-bundle.tar.gz").apply { writeBytes(byteArrayOf(1)) }

        assertTrue(cache.holds(written))
        assertFalse(cache.holds(beside))
        assertFalse(cache.holds(directory))

        cache.sweep()

        assertFalse(cache.holds(written))
    }
}
