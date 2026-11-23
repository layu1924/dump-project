<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center p-4 sm:p-6 overflow-hidden bg-gradient-to-br from-slate-50 via-pink-50/40 to-purple-50/50">
            
            <!-- Latar Interaktif Canvas -->
            <canvas id="interactive-bg" class="fixed inset-0 w-full h-full pointer-events-none z-0"></canvas>

            <!-- Satu Kartu Menyatu (Membungkus Logo + Form Login) -->
            <div class="relative z-10 w-full sm:max-w-md px-8 pt-8 pb-8 bg-white/85 backdrop-blur-md shadow-xl shadow-fuchsia-950/10 border border-white/80 overflow-hidden sm:rounded-2xl">
                
                <!-- Kontainer Logo (Di Depan & Di Dalam Bagian Atas Card) -->
                <div class="relative z-20 flex justify-center mb-6 pb-5 border-b border-fuchsia-900/10">
                    <a href="/" class="transition-transform duration-300 hover:scale-105">
                        <x-application-logo class="w-48 h-auto mx-auto" />
                    </a>
                </div>

                <!-- Form Login -->
                <div class="relative z-20">
                    {{ $slot }}
                </div>

            </div>
        </div>

        {{-- Biarkan blok <script> partikel repulse yang terakhir tetap berada di sini --}}

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('interactive-bg');
                const ctx = canvas.getContext('2d');

                let particles = [];
                const maxParticles = 150;
                const maxClickParticles = 190;
                const connectionDistance = 155;
                const mouseRadius = 185;    // Radius garis terhubung ke kursor
                const repulseRadius = 100;  // Radius dorong halus agar partikel tidak menumpuk

                const mouse = { x: null, y: null };

                function resizeCanvas() {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                }

                window.addEventListener('resize', resizeCanvas);
                resizeCanvas();

                window.addEventListener('mousemove', (e) => {
                    mouse.x = e.clientX;
                    mouse.y = e.clientY;
                });

                window.addEventListener('mouseout', () => {
                    mouse.x = null;
                    mouse.y = null;
                });

                window.addEventListener('click', (e) => {
                    if (e.target.closest('form') || e.target.closest('a')) return;
                    for (let i = 0; i < 4; i++) {
                        if (particles.length > maxClickParticles) particles.shift();
                        particles.push(new Particle(e.clientX, e.clientY));
                    }
                });

                class Particle {
                    constructor(x, y) {
                        this.x = x ?? Math.random() * canvas.width;
                        this.y = y ?? Math.random() * canvas.height;
                        this.vx = (Math.random() - 0.5) * 1.1;
                        this.vy = (Math.random() - 0.5) * 1.1;
                        this.radius = Math.random() * 2.4 + 1.6;
                    }

                    update() {
                        this.x += this.vx;
                        this.y += this.vy;

                        // Pantulkan jika menyentuh tepi layar
                        if (this.x <= 0 || this.x >= canvas.width) this.vx *= -1;
                        if (this.y <= 0 || this.y >= canvas.height) this.vy *= -1;

                        // Dorong halus MENJAUH jika terlalu dekat dengan kursor
                        if (mouse.x !== null && mouse.y !== null) {
                            const dx = this.x - mouse.x;
                            const dy = this.y - mouse.y;
                            const dist = Math.hypot(dx, dy);

                            if (dist < repulseRadius && dist > 0) {
                                const force = (repulseRadius - dist) / repulseRadius;
                                this.x += (dx / dist) * force * 2.2;
                                this.y += (dy / dist) * force * 2.2;
                            }
                        }

                        // Jaga agar partikel tidak terdorong keluar batas layar
                        this.x = Math.max(0, Math.min(canvas.width, this.x));
                        this.y = Math.max(0, Math.min(canvas.height, this.y));
                    }

                    draw() {
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(122, 0, 75, 0.65)';
                        ctx.fill();
                    }
                }

                for (let i = 0; i < maxParticles; i++) {
                    particles.push(new Particle());
                }

                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    for (let i = 0; i < particles.length; i++) {
                        const p1 = particles[i];
                        p1.update();
                        p1.draw();

                        for (let j = i + 1; j < particles.length; j++) {
                            const p2 = particles[j];
                            const dist = Math.hypot(p1.x - p2.x, p1.y - p2.y);

                            if (dist < connectionDistance) {
                                const opacity = (1 - dist / connectionDistance) * 0.42;
                                ctx.beginPath();
                                ctx.strokeStyle = `rgba(122, 0, 75, ${opacity})`;
                                ctx.lineWidth = 1;
                                ctx.moveTo(p1.x, p1.y);
                                ctx.lineTo(p2.x, p2.y);
                                ctx.stroke();
                            }
                        }

                        // Garis jaring tetap menyambung ke kursor tanpa menarik partikelnya
                        if (mouse.x !== null && mouse.y !== null) {
                            const mouseDist = Math.hypot(p1.x - mouse.x, p1.y - mouse.y);
                            if (mouseDist < mouseRadius) {
                                const opacity = (1 - mouseDist / mouseRadius) * 0.55;
                                ctx.beginPath();
                                ctx.strokeStyle = `rgba(155, 28, 102, ${opacity})`;
                                ctx.lineWidth = 1.2;
                                ctx.moveTo(p1.x, p1.y);
                                ctx.lineTo(mouse.x, mouse.y);
                                ctx.stroke();
                            }
                        }
                    }

                    requestAnimationFrame(animate);
                }

                animate();
            });
        </script>
    </body>
</html>