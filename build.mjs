import { build } from 'esbuild'
import { execFileSync } from 'node:child_process'
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = dirname(fileURLToPath(import.meta.url))
const distDir = resolve(root, 'resources/dist')
const jsEntry = resolve(root, 'resources/js/filament-chatbot.js')
const cssEntry = resolve(root, 'resources/css/filament-chatbot.css')
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

const mergedCss = [
    readFileSync(resolve(root, 'node_modules/katex/dist/katex.min.css'), 'utf8'),
    readFileSync(cssEntry, 'utf8'),
].join('\n')

const tempCssInput = resolve(distDir, 'filament-chatbot.bundle.css')

writeFileSync(tempCssInput, mergedCss)

execFileSync('npx', ['cleancss', '-o', cssOutput, tempCssInput], {
    cwd: root,
    stdio: 'inherit',
})
