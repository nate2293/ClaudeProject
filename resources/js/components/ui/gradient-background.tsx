import { cn } from '@/lib/utils';
import { type ReactNode, useEffect, useRef } from 'react';

export type BlurSize = 'none' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl';

/** Four #rrggbb colours: base, wave, highlight, accent. */
export type GradientPalette = [string, string, string, string];

export interface GradientBackgroundProps {
    /** Frosted blur over the animated canvas. */
    backdropBlurAmount?: BlurSize;
    /** Classes for the outer container (size, rounding, border…). */
    className?: string;
    /** Colours in light mode. */
    lightColors?: GradientPalette;
    /** Colours in dark mode. */
    darkColors?: GradientPalette;
    /** Animation speed multiplier (1 = default, 0 = still). */
    speed?: number;
    /** Content shown over the background. */
    children?: ReactNode;
}

// The shop's cloth colours: chambray, oat and denim by day; indigo and night by night.
const LIGHT: GradientPalette = ['#e3e7ee', '#b9c6e2', '#f3ede2', '#8fa3cf'];
const DARK: GradientPalette = ['#0e1424', '#1f2b47', '#3d5a96', '#56637f'];

const blurClass: Record<BlurSize, string> = {
    none: '',
    sm: 'backdrop-blur-sm',
    md: 'backdrop-blur-md',
    lg: 'backdrop-blur-lg',
    xl: 'backdrop-blur-xl',
    '2xl': 'backdrop-blur-2xl',
    '3xl': 'backdrop-blur-3xl',
};

const VERTEX = `attribute vec2 p; void main() { gl_Position = vec4(p, 0.0, 1.0); }`;

// Domain-warped noise: colour waves that fold over each other, a bright ridge
// where they meet (the "extrusion glow") and a soft vignette.
const FRAGMENT = `
precision mediump float;
uniform vec2 u_res;
uniform float u_time;
uniform vec3 u_c1, u_c2, u_c3, u_c4;

float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
float noise(vec2 p) {
    vec2 i = floor(p), f = fract(p);
    vec2 u = f * f * (3.0 - 2.0 * f);
    return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x), mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);
}
float fbm(vec2 p) {
    float v = 0.0, a = 0.5;
    for (int i = 0; i < 5; i++) { v += a * noise(p); p *= 2.02; a *= 0.5; }
    return v;
}

void main() {
    vec2 uv = gl_FragCoord.xy / u_res;
    vec2 p = uv * vec2(u_res.x / u_res.y, 1.0);
    float t = u_time * 0.06;

    vec2 q = vec2(fbm(p * 1.4 + vec2(0.0, t)), fbm(p * 1.4 + vec2(5.2, -t)));
    vec2 r = vec2(fbm(p * 1.8 + q * 2.0 + vec2(1.7, 9.2) + t * 1.2), fbm(p * 1.8 + q * 2.0 + vec2(8.3, 2.8) - t));
    float f = fbm(p * 1.5 + r * 2.2);

    vec3 col = mix(u_c1, u_c2, smoothstep(0.15, 0.85, f));
    col = mix(col, u_c3, smoothstep(0.35, 0.95, length(q)) * 0.85);
    col = mix(col, u_c4, smoothstep(0.55, 1.0, r.x) * 0.7);

    float ridge = smoothstep(0.035, 0.0, abs(f - 0.55));
    col += ridge * 0.12;

    float vignette = smoothstep(1.15, 0.3, length(uv - 0.5) * 1.35);
    col *= mix(0.86, 1.0, vignette);

    gl_FragColor = vec4(col, 1.0);
}`;

const rgb = (hex: string) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255);

/**
 * An animated WebGL gradient of slowly folding colour waves. Purely decorative
 * (hidden from assistive technology); content goes in `children`.
 *
 * Cheap to run: renders at most 1.5× pixel density, pauses when scrolled out of
 * view or the tab is hidden, shows a still frame for reduced-motion users, and
 * falls back to a CSS gradient where WebGL isn't available. Follows the site's
 * light/dark theme.
 */
export function GradientBackground({ backdropBlurAmount = 'none', className, lightColors = LIGHT, darkColors = DARK, speed = 1, children }: GradientBackgroundProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const fallbackRef = useRef<HTMLDivElement>(null);
    const paletteKey = [...lightColors, ...darkColors].join();

    useEffect(() => {
        const canvas = canvasRef.current;
        const gl = canvas?.getContext('webgl', { antialias: false, premultipliedAlpha: false, powerPreference: 'low-power' });
        if (!canvas || !gl) {
            canvas?.remove();
            return; // The CSS fallback underneath stays visible.
        }

        const compile = (type: number, source: string) => {
            const shader = gl.createShader(type)!;
            gl.shaderSource(shader, source);
            gl.compileShader(shader);
            return shader;
        };
        const program = gl.createProgram()!;
        gl.attachShader(program, compile(gl.VERTEX_SHADER, VERTEX));
        gl.attachShader(program, compile(gl.FRAGMENT_SHADER, FRAGMENT));
        gl.linkProgram(program);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
            canvas.remove();
            return;
        }
        gl.useProgram(program);

        gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        const position = gl.getAttribLocation(program, 'p');
        gl.enableVertexAttribArray(position);
        gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

        const uRes = gl.getUniformLocation(program, 'u_res');
        const uTime = gl.getUniformLocation(program, 'u_time');
        const uColours = ['u_c1', 'u_c2', 'u_c3', 'u_c4'].map((name) => gl.getUniformLocation(program, name));

        const applyPalette = () => {
            const palette = document.documentElement.classList.contains('dark') ? darkColors : lightColors;
            palette.forEach((hex, i) => gl.uniform3fv(uColours[i], rgb(hex)));
        };

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            canvas.width = Math.max(1, Math.round(canvas.clientWidth * dpr));
            canvas.height = Math.max(1, Math.round(canvas.clientHeight * dpr));
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.uniform2f(uRes, canvas.width, canvas.height);
        };

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || speed === 0;
        const start = performance.now() - 20000; // Begin mid-flow rather than from a blank pattern.
        let frame = 0;
        let visible = true;

        const draw = () => {
            gl.uniform1f(uTime, ((performance.now() - start) / 1000) * (reducedMotion ? 0 : speed));
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        };
        const loop = () => {
            draw();
            frame = requestAnimationFrame(loop);
        };
        const play = () => {
            cancelAnimationFrame(frame);
            if (reducedMotion) draw();
            else if (visible && !document.hidden) loop();
        };

        applyPalette();
        resize();
        if (fallbackRef.current) fallbackRef.current.style.opacity = '0';
        play();

        const resizeObserver = new ResizeObserver(() => {
            resize();
            if (reducedMotion) draw();
        });
        resizeObserver.observe(canvas);
        const viewObserver = new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            play();
        });
        viewObserver.observe(canvas);
        const themeObserver = new MutationObserver(() => {
            applyPalette();
            if (reducedMotion) draw();
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        document.addEventListener('visibilitychange', play);

        return () => {
            cancelAnimationFrame(frame);
            resizeObserver.disconnect();
            viewObserver.disconnect();
            themeObserver.disconnect();
            document.removeEventListener('visibilitychange', play);
            gl.getExtension('WEBGL_lose_context')?.loseContext();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- palette arrays are compared by value via paletteKey.
    }, [paletteKey, speed]);

    return (
        <div className={cn('relative isolate overflow-hidden', className)}>
            {/* Shown until WebGL draws, and instead of it where WebGL isn't available. Follows the theme. */}
            <div ref={fallbackRef} aria-hidden className="absolute inset-0 -z-20 transition-opacity duration-700">
                <div className="absolute inset-0 dark:hidden" style={{ background: `linear-gradient(135deg, ${lightColors[0]}, ${lightColors[1]} 50%, ${lightColors[2]})` }} />
                <div className="absolute inset-0 hidden dark:block" style={{ background: `linear-gradient(135deg, ${darkColors[0]}, ${darkColors[1]} 50%, ${darkColors[2]})` }} />
            </div>
            <canvas ref={canvasRef} aria-hidden className="absolute inset-0 -z-20 h-full w-full" />
            {backdropBlurAmount !== 'none' && <div aria-hidden className={cn('absolute inset-0 -z-10', blurClass[backdropBlurAmount])} />}
            {children}
        </div>
    );
}

export default GradientBackground;
