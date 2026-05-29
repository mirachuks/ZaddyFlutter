<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZaddyExpress | Premium Logistics & Freight Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            green: '#0B4F30',
                            orange: '#F77F00',
                            lightBg: '#F9FBF9'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-800 font-sans antialiased min-h-screen flex flex-col">

    <div class="bg-brand-green text-emerald-100 text-xs py-2.5 px-6 hidden sm:block">
        <div class="max-w-7xl mx-auto flex justify-between items-center font-medium">
            <div class="flex items-center space-x-6">
                <span><i class="fa-solid fa-headset text-brand-orange mr-2"></i>Support: support@zaddyexpress.com</span>
                <span><i class="fa-solid fa-bolt text-brand-orange mr-2"></i>Next-Day Express Priority Routes Active</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span>All Cargo Terminals Operational</span>
            </div>
        </div>
    </div>

    <nav class="bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100 shadow-sm transition-all">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center space-x-3 group">
                <div class="bg-brand-green text-white p-2.5 rounded-xl shadow-md transition-transform group-hover:scale-105">
                    <i class="fa-solid fa-truck-fast text-lg text-brand-orange"></i>
                </div>
                <span class="text-xl font-black tracking-tight text-brand-green uppercase">ZADDY<span class="text-brand-orange font-light">EXPRESS</span></span>
            </a>
            
            <div class="hidden lg:flex space-x-8 font-bold text-xs uppercase tracking-wider items-center">
                <a href="#" class="text-brand-green hover:text-brand-orange transition-colors">Tracking Hub</a>
                <a href="#" class="text-slate-600 hover:text-brand-green transition-colors">Our Services</a>
                <a href="#" class="text-slate-600 hover:text-brand-green transition-colors">Domestic Shipping</a>
                <a href="#" class="text-slate-600 hover:text-brand-green transition-colors">Contact Support</a>
                <a href="#" class="bg-brand-orange text-white px-5 py-3 rounded-xl shadow-md shadow-orange-500/10 hover:bg-orange-600 transition font-extrabold tracking-widest text-[11px]">GET A QUOTE</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        
        <section class="relative bg-gradient-to-b from-brand-lightBg to-white py-24 md:py-32 px-6 overflow-hidden">
            <div class="absolute inset-0 opacity-40 bg-[linear-gradient(to_right,#0b4f30_1px,transparent_1px),linear-gradient(to_bottom,#0b4f30_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
            
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10">
                <div class="lg:col-span-7 space-y-6">
                    <div class="inline-flex items-center space-x-2 bg-emerald-50 border border-emerald-200 px-4 py-1.5 rounded-full">
                        <i class="fa-solid fa-shield-halved text-brand-green text-xs"></i>
                        <span class="text-[11px] font-bold tracking-wider uppercase text-brand-green">Secured Last-Mile Logistics Infrastructure</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black tracking-tight leading-tight text-slate-900 uppercase">
                        Smart Logistics <br>For Modern <span class="text-brand-green">Businesses</span>
                    </h1>
                    <p class="text-base text-slate-600 max-w-xl leading-relaxed">
                        ZaddyExpress combines regional fleet automation with instant custom routing protocols, delivering elite, reliable delivery pipelines across the continent.
                    </p>
                    
                    <div class="flex flex-wrap gap-4 pt-2">
                        <a href="#" class="bg-brand-green hover:bg-emerald-900 text-white font-extrabold px-6 py-3.5 rounded-xl shadow-lg shadow-emerald-900/10 transition text-xs tracking-wider uppercase">Explore Fleet Capability</a>
                        <a href="#" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold px-6 py-3.5 rounded-xl transition text-xs tracking-wider uppercase">Corporate Client Portal</a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 p-8 border border-slate-100 relative">
                        <div class="absolute -top-3 left-6 bg-brand-orange text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-md shadow-sm">
                            Real-Time Tracker
                        </div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-brand-green mb-1">Track Your Shipment</h3>
                        <p class="text-xs text-slate-400 mb-6">Enter your consignment tracking token key to locate payload logistics metadata.</p>
                        
                        <form action="#" method="GET" class="space-y-4">
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass-location absolute left-4 top-4 text-slate-400 text-base"></i>
                                <input type="text" name="tracking_id" placeholder="CONSIGNMENT ID (E.G., ZX-9810-LG)" 
                                       class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-brand-green font-mono text-sm uppercase tracking-wider font-bold transition-all" required>
                            </div>
                            <button type="submit" class="w-full bg-brand-green hover:bg-emerald-900 text-white font-black py-4 rounded-xl shadow-md transition-all uppercase tracking-widest text-xs flex items-center justify-center space-x-2">
                                <span>Scan Transit Network</span>
                                <i class="fa-solid fa-satellite-dish text-brand-orange"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-6 py-24">
            <div class="text-center max-w-xl mx-auto mb-16">
                <span class="text-brand-orange font-black uppercase text-xs tracking-widest">Core Capabilities</span>
                <h2 class="text-3xl font-black text-brand-green uppercase tracking-tight mt-1">Our Premium Cargo Streams</h2>
                <div class="w-12 h-1 bg-brand-orange mx-auto mt-4 rounded"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-brand-lightBg p-8 rounded-2xl border border-emerald-100/40 hover:border-brand-green/30 transition-all shadow-sm group">
                    <div class="w-12 h-12 rounded-xl bg-white border border-emerald-100 text-brand-green flex items-center justify-center text-lg mb-6 group-hover:bg-brand-green group-hover:text-white transition-colors">
                        <i class="fa-solid fa-truck text-brand-orange group-hover:text-white"></i>
                    </div>
                    <h3 class="text-base font-black text-brand-green uppercase tracking-tight mb-2">Same-Day Local Delivery</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Dedicated regional routing systems built to secure and finalize hyper-local business fulfillment pipelines cleanly and quickly.</p>
                </div>
                <div class="bg-brand-lightBg p-8 rounded-2xl border border-emerald-100/40 hover:border-brand-green/30 transition-all shadow-sm group">
                    <div class="w-12 h-12 rounded-xl bg-white border border-emerald-100 text-brand-green flex items-center justify-center text-lg mb-6 group-hover:bg-brand-green group-hover:text-white transition-colors">
                        <i class="fa-solid fa-warehouse text-brand-orange group-hover:text-white"></i>
                    </div>
                    <h3 class="text-base font-black text-brand-green uppercase tracking-tight mb-2">Secure Fulfillment Hubs</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Temperature-monitored distribution storage infrastructure designed to manage and sort high-volume priority commercial parcels.</p>
                </div>
                <div class="bg-brand-lightBg p-8 rounded-2xl border border-emerald-100/40 hover:border-brand-green/30 transition-all shadow-sm group">
                    <div class="w-12 h-12 rounded-xl bg-white border border-emerald-100 text-brand-green flex items-center justify-center text-lg mb-6 group-hover:bg-brand-green group-hover:text-white transition-colors">
                        <i class="fa-solid fa-shield text-brand-orange group-hover:text-white"></i>
                    </div>
                    <h3 class="text-base font-black text-brand-green uppercase tracking-tight mb-2">Escrow Protected Delivery</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Integrated custom verification safeguards guaranteeing package security from dispatch initialization to final step closure handles.</p>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-slate-50 border-t border-slate-100 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h4 class="text-brand-green font-black uppercase text-xs tracking-widest mb-4 border-l-2 border-brand-orange pl-2">ZaddyExpress</h4>
                <p class="leading-relaxed">Modern commercial courier transport pipelines engineered to stabilize retail, corporate, and decentralized marketplace last-mile infrastructure.</p>
            </div>
            <div>
                <h4 class="text-brand-green font-black uppercase text-xs tracking-widest mb-4 border-l-2 border-brand-orange pl-2">Operations Node</h4>
                <p class="mb-1"><i class="fa-solid fa-location-dot text-brand-orange mr-2"></i>Central Administration Logistics Terminal Base</p>
                <p><i class="fa-solid fa-envelope text-brand-orange mr-2"></i>support@zaddyexpress.com</p>
            </div>
            <div>
                <h4 class="text-brand-green font-black uppercase text-xs tracking-widest mb-4 border-l-2 border-brand-orange pl-2">Trust System</h4>
                <p class="leading-relaxed">All consignment cargo is recorded using end-to-end route matrix tracking. Secure package handoffs are strictly enforced at every regional hub marker.</p>
            </div>
        </div>
        <div class="bg-slate-100 py-4 text-center border-t border-slate-200/60 font-medium text-slate-400">
            &copy; 2026 ZaddyExpress Logistics. Sleek, verified, and structured transit networks.
        </div>
    </footer>

</body>
</html>