import { build } from 'esbuild'
import { mkdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = dirname(fileURLToPath(import.meta.url))
const distDir = resolve(root, 'resources/dist')
const jsEntry = resolve(root, 'resources/js/filament-chatbot.js')
const cssEntry = resolve(root, 'resources/css/filament-chatbot-build.css')
const jsOutput = resolve(distDir, 'filament-chatbot.min.js')
const cssOutput = resolve(distDir, 'filament-chatbot.min.css')

mkdirSync(distDir, { recursive: true })

await build({
    entryPoints: [jsEntry],
    outfile: jsOutput,
    bundle: true,
    minify: true,
    format: 'iife',
    platform: 'browser',
    target: ['es2020'],
})
await build({
    entryPoints: [cssEntry],
    outfile: cssOutput,
    bundle: true,
    minify: true,
    loader: {
        '.woff': 'dataurl',
        '.woff2': 'dataurl',
        '.ttf': 'dataurl',
    },
    target: ['es2020'],
})
