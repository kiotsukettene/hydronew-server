import { Droplet, Filter, LayoutDashboard, Leaf, User } from "lucide-react";
import React, { useEffect, useRef, useState } from "react";



function FeaturesSection() {
  const [sectionVisible, setSectionVisible] = useState(false);
  const sectionRef = useRef(null);

  useEffect(() => {
    if (typeof document === "undefined") return;
    const id = "bento2-animations";
    if (document.getElementById(id)) return;
    const style = document.createElement("style");
    style.id = id;
    style.innerHTML = `
      @keyframes bento2-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6%); }
      }
      @keyframes bento2-pulse {
        0%, 100% { transform: scale(1); opacity: 0.85; }
        50% { transform: scale(1.08); opacity: 1; }
      }
      @keyframes bento2-tilt {
        0% { transform: rotate(-2deg); }
        50% { transform: rotate(2deg); }
        100% { transform: rotate(-2deg); }
      }
      @keyframes bento2-drift {
        0%, 100% { transform: translate3d(0, 0, 0); }
        50% { transform: translate3d(6%, -6%, 0); }
      }
      @keyframes bento2-glow {
        0%, 100% { opacity: 0.6; filter: drop-shadow(0 0 0 rgba(0,0,0,0.4)); }
        50% { opacity: 1; filter: drop-shadow(0 0 6px rgba(0,0,0,0.2)); }
      }
      @keyframes bento2-intro {
        0% { opacity: 0; transform: translate3d(0, 28px, 0); }
        100% { opacity: 1; transform: translate3d(0, 0, 0); }
      }
      @keyframes bento2-card {
        0% { opacity: 0; transform: translate3d(0, 18px, 0) scale(0.96); }
        100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
      }
    `;
    document.head.appendChild(style);
    return () => {
      style.remove();
    };
  }, []);



  useEffect(() => {
    if (!sectionRef.current || typeof window === "undefined") return;

    const node = sectionRef.current;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setSectionVisible(true);
          observer.disconnect();
        }
      });
    }, { threshold: 0.25 });

    observer.observe(node);

    return () => observer.disconnect();
  }, []);


  const features = [
    {
      title: "Clean Water Filtration",
      blurb: "Keeps your hydroponic water free from dirt and harmful particles to ensure your plants receive only the cleanest water for growth.",
      icon: Filter,
      animation: "bento2-float 6s ease-in-out infinite",
    },
    {
      title: "Water Monitoring",
      blurb: "Tracks important water details such as pH, level, and quality in real time, helping you maintain the best environment for your lettuce.",
      icon: Droplet,
      animation: "bento2-pulse 4s ease-in-out infinite",
    },
    {
      title: "Dashboard",
      blurb: "Displays all your system data in one easy-to-read view, so you can check water condition, plant health, and updates anytime.",
      icon: LayoutDashboard,
      animation: "bento2-tilt 5.5s ease-in-out infinite",
    },
    {
      title: "Hydroponics Monitoring",
      blurb: "Monitors your entire hydroponic setup — from growth progress to nutrient balance, ensuring your plants are always in ideal condition.",
      icon: Leaf,
      animation: "bento2-drift 8s ease-in-out infinite",
    },
    {
      title: "Account Management",
      blurb: "Allows users to create and manage their own accounts, keeping their hydroponic data secure and personalized for better monitoring.",
      icon: User,
      animation: "bento2-glow 7s ease-in-out infinite",
    },
  ];

  const spans = [
    "md:col-span-4 md:row-span-2",
    "md:col-span-2 md:row-span-1",
    "md:col-span-2 md:row-span-1",
    "md:col-span-3 md:row-span-1",
    "md:col-span-3 md:row-span-1",
  ];

  return (
    <div
      id="features"
      className="relative min-h-screen w-full bg-white text-neutral-900 transition-colors duration-500 dark:bg-black dark:text-white">
      <div className="absolute inset-0 -z-30 overflow-hidden">
        <div
          className="absolute inset-0 [--aurora-base:#ffffff] [--aurora-accent:rgba(148,163,184,0.15)] dark:[--aurora-base:#040404] dark:[--aurora-accent:rgba(59,130,246,0.15)]"
          style={{
            background:
              "radial-gradient(ellipse 55% 100% at 12% 0%, var(--aurora-accent), transparent 65%), radial-gradient(ellipse 40% 80% at 88% 0%, rgba(148,163,184,0.1), transparent 70%), var(--aurora-base)",
          }} />
        <div
          className="absolute inset-0 [--grid-color:rgba(17,17,17,0.08)] dark:[--grid-color:rgba(255,255,255,0.06)]"
          style={{
            backgroundImage:
              "linear-gradient(to right, var(--grid-color) 1px, transparent 1px), linear-gradient(to bottom, var(--grid-color) 1px, transparent 1px)",
            backgroundSize: "20px 20px",
            backgroundPosition: "0 0, 0 0",
            maskImage:
              "repeating-linear-gradient(to right, black 0px, black 3px, transparent 3px, transparent 8px), repeating-linear-gradient(to bottom, black 0px, black 3px, transparent 3px, transparent 8px)",
            WebkitMaskImage:
              "repeating-linear-gradient(to right, black 0px, black 3px, transparent 3px, transparent 8px), repeating-linear-gradient(to bottom, black 0px, black 3px, transparent 3px, transparent 8px)",
            maskComposite: "intersect",
            WebkitMaskComposite: "source-in",
            opacity: 0.9,
          }} />
        <div
          className="pointer-events-none absolute inset-0 [--edge-color:rgba(255,255,255,1)] dark:[--edge-color:rgba(0,0,0,1)]"
          style={{
            background:
              "radial-gradient(circle at center, rgba(0,0,0,0) 55%, var(--edge-color) 100%)",
            filter: "blur(40px)",
            opacity: 0.75,
          }} />
      </div>
      <section
        ref={sectionRef}
        className={`relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20 motion-safe:opacity-0 ${
          sectionVisible ? "motion-safe:animate-[bento2-intro_0.9s_ease-out_forwards]" : ""
        }`}>
        <header
          className="mb-10 flex flex-col gap-6 border-b border-neutral-900/10 pb-6 transition-colors duration-500 md:flex-row md:items-end md:justify-between dark:border-white/10">
          <div className="flex flex-col gap-2">
            <span
              className="text-md uppercase tracking-[0.35em] text-neutral-500 transition-colors duration-500 dark:text-white/40">
              FEATURES
            </span>
            <h2  style={{ fontFamily: '"Viaoda Libre", serif' }}
              className="text-3xl font-black tracking-tight text-neutral-900 transition-colors duration-500 md:text-5xl dark:text-white">
What HydroNew Can Do?            </h2>
          </div>
          
        </header>

        <div
          className="grid grid-cols-1 gap-3 md:auto-rows-[minmax(120px,auto)] md:grid-cols-6">
          {features.map((feature, index) => (
            <BentoItem
              key={feature.title}
              span={spans[index]}
              feature={feature}
              index={index}
              isVisible={sectionVisible} />
          ))}
        </div>

      </section>
    </div>
  );
}

function BentoItem({ feature, span = "", index = 0, isVisible = false }) {
  const { icon: Icon, animation, title, blurb,} = feature;
 
  const animationDelay = `${Math.max(index * 0.12, 0)}s`;

  return (
    <article
      className={`group relative flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-neutral-900/10 bg-white/80 p-5 shadow-[0_10px_40px_rgba(0,0,0,0.04)] transition-transform duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_20px_60px_rgba(0,0,0,0.08)] motion-safe:opacity-0 ${
        isVisible ? "motion-safe:animate-[bento2-card_0.8s_ease-out_forwards]" : ""
      } dark:border-white/10 dark:bg-white/5 dark:shadow-[0_18px_40px_rgba(0,0,0,0.35)] dark:hover:shadow-[0_28px_70px_rgba(0,0,0,0.55)] ${span}`}
      style={{ animationDelay }}>
      <div className="absolute inset-0 -z-10 overflow-hidden rounded-2xl">
        <div
          className="absolute inset-0 bg-white/85 transition-colors duration-500 dark:bg-white/8" />
        <div
          className="absolute inset-0 opacity-70 transition-opacity duration-500 dark:opacity-60"
         />
      </div>
      <div className="flex items-start gap-4">
        <div
          className="flex h-12 w-12 items-center justify-center rounded-full border border-neutral-900/15 bg-white transition-colors duration-500 dark:border-white/15 dark:bg-white/10">
          <Icon
            className="h-7 w-7 text-neutral-900 transition-colors duration-500 dark:text-white"
            strokeWidth={1.5}
            style={{ animation }} />
        </div>
        <div className="flex-1">
          <header className="flex items-start gap-3">
            <h3
              className="text-base font-semibold uppercase tracking-wide text-neutral-900 transition-colors duration-500 dark:text-white">
              {title}
            </h3>
            
          </header>
          <p
            className="mt-2 text-sm leading-relaxed text-neutral-600 transition-colors duration-500 dark:text-white/60">
            {blurb}
          </p>
        </div>
      </div>
      <div
        className="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
        <div
          className="absolute inset-0 rounded-2xl border border-neutral-900/10 transition-colors duration-500 dark:border-white/10"
          style={{
            maskImage:
              "radial-gradient(220px_220px_at_var(--x,50%)_var(--y,50%), black, transparent)",
            WebkitMaskImage:
              "radial-gradient(220px_220px_at_var(--x,50%)_var(--y,50%), black, transparent)",
          }} />
      </div>
    </article>
  );
}

export default FeaturesSection;
export { FeaturesSection };

