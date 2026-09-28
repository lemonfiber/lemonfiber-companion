package app.lemonfiber.native

import java.io.File

/**
 * The one directory a file handed to the platform's sheet is written to.
 *
 * The chosen app has to be able to read the file after the sheet has closed,
 * and nothing on this side learns when it is done. So the bound is on the
 * directory instead: one directory inside the app's own cache, used for nothing
 * else, emptied before every new handover and on the next launch. At most one
 * file is ever in it.
 *
 * No Android framework in sight: a directory and some bytes, which is what lets
 * it be run on a JVM against a temporary directory rather than demonstrated on
 * a handset. The grant and the sheet are `HandoverFunctions.kt`'s.
 *
 * Deliberately mirrors `ShareCache.swift`.
 */
public class ShareCache(
    /** The directory this cache owns, whole. Nothing else writes into it. */
    private val directory: File,
) {
    /** Take the directory away, and whatever was handed over last with it. */
    public fun sweep() {
        directory.deleteRecursively()
    }

    /**
     * Sweep, then write the bytes under that name, and answer the file.
     *
     * Answers nothing where there is nothing to write, where the name is not
     * one file's name, or where the file could not be written. The sweep
     * happens either way, so a refused handover leaves nothing behind either.
     */
    public fun write(
        name: String,
        bytes: ByteArray,
    ): File? {
        sweep()

        if (bytes.isEmpty() || !isAFileName(name)) {
            return null
        }

        return runCatching {
            directory.mkdirs()
            File(directory, name).apply { writeBytes(bytes) }
        }.getOrNull()
    }

    /**
     * Whether this is the file the cache holds: the only file a read grant is
     * ever given for.
     */
    public fun holds(file: File): Boolean =
        file.isFile && file.canonicalFile.parentFile == directory.canonicalFile

    /** What names the directory, and what a file in it may be called. */
    public companion object {
        /** What the directory is called inside the app's own cache. */
        public const val DIRECTORY: String = "lemonfiber-handover"

        /** The cache whose directory sits inside that cache directory. */
        public fun inside(caches: File): ShareCache = ShareCache(File(caches, DIRECTORY))

        /** Whether a name is one file's name, rather than nothing or a way out of the directory. */
        public fun isAFileName(name: String): Boolean =
            name.isNotEmpty() &&
                name != "." &&
                name != ".." &&
                name.none { it == '/' || it == '\\' || it == '\u0000' }
    }
}
