import React, { useState, useMemo, useEffect } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import IPhoneMockup from "./iphone-mockup";
import { Button } from "./button";

export function HeroSection() {
  const slides = useMemo(
    () => [
      "/images/monitor.png",
      "/images/hydroponics.png",
      "/images/filtration.png",
      "/images/dashboard.png",
      "/images/account.png",
    ],
    []
  );

  const [index, setIndex] = useState(3);
  const [revealSides, setRevealSides] = useState(false);
  const total = slides.length;

  const next = () => setIndex((prev) => (prev + 1) % total);
  const prev = () => setIndex((prev) => (prev - 1 + total) % total);

  const left2 = (index - 2 + total) % total;
  const left1 = (index - 1 + total) % total;
  const right1 = (index + 1) % total;
  const right2 = (index + 2) % total;

  useEffect(() => {
    const handleScroll = () => {
      const scrolled = window.scrollY > 10;
      setRevealSides(scrolled);
    };
    // initialize based on current position
    handleScroll();
    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
      <section className="min-h-screen w-full relative overflow-hidden  pt-48 pb-12 bg-white">
  {/* Soft Yellow Glow */}
  <div
    className="absolute inset-0 z-0"
    style={{
      backgroundImage: `
        radial-gradient(circle at center, #D7E7BA 0%, transparent 70%)
      `,
      opacity: 0.6,
      mixBlendMode: "multiply",
    }}
  />
     {/* =========================== Main Content =========================== */}

      {/* Header */}
      <div className="text-center ">
        <h1 className="text-5xl md:text-6xl font-bold text-[#1b1b1b] tracking-normal ">
          Grow with Nature, <br />Powered by <span style={{ fontFamily: '"Viaoda Libre", serif' }} className="font-semibold text-neutral-700">Technology</span>
        </h1>
        <p className="mt-4 text-gray-700 max-w-2xl mx-auto">
          HydroNew ensures clean and balanced water for your hydroponic system, helping your plants grow healthier and stronger.
        </p>
        <button className="mt-8 px-8 py-3 rounded-full bg-black text-white font-medium hover:bg-neutral-800 transition">
          Learn More
        </button>
      </div>

      {/* Screens Row */}
      <div className="relative flex items-center justify-center max-w-7xl mx-auto gap-6 -mt-10 ">
        {/* Left 2 (furthest) */}
        <div className={`${revealSides ? "opacity-70 scale-80 -rotate-6" : "opacity-0 scale-50 -rotate-6"} transition-all duration-500 ease-in-out`}>
          <div className="w-[230px] h-full overflow-hidden rounded-3xl shadow ring-8 ring-gray-600/10">
            <img src={slides[left2]} alt="left2" className="object-cover w-full h-full" />
          </div>
        </div>

        {/* Left 1 (closer) */}
        <div className={`${revealSides ? "opacity-80 scale-85 -rotate-2" : "opacity-0 scale-50 -rotate-2"} transition-all duration-500 ease-in-out`}>
          <div className="w-[250px] h-full overflow-hidden rounded-3xl shadow ring-8 ring-gray-600/10">
            <img src={slides[left1]} alt="left1" className="object-cover w-full h-full" />
          </div>
        </div>

        {/* Center mockup with soft glow */}
        <div className="relative z-10">
          {/* Glow background */}
          <div className="absolute inset-0 -z-10 flex justify-center">
            <div className="w-[100px] h-[100px] bg-[#a3ffba] blur-3xl rounded-full opacity-70" />
          </div>

          {/* Main phone mockup (slightly smaller) */}
          <div className="scale-[0.70] transition-all  duration-500 ease-in-out ">
           <IPhoneMockup
  color="space-black"
  wallpaper={slides[index]}
  wallpaperFit="cover"
  wallpaperPosition="center"
/>

          </div>
        </div>

        {/* Right 1 (closer) */}
        <div className={`${revealSides ? "opacity-80 scale-85 rotate-2" : "opacity-0 scale-50 rotate-2"} transition-all duration-500 ease-in-out`}>
          <div className="w-[250px] h-full overflow-hidden rounded-3xl shadow ring-8 ring-gray-600/10">
            <img src={slides[right1]} alt="right1" className="object-cover w-full h-full" />
          </div>
        </div>

        {/* Right 2 (furthest) */}
        <div className={`${revealSides ? "opacity-70 scale-80 rotate-6" : "opacity-0 scale-50 rotate-6"} transition-all duration-500 ease-in-out`}>
          <div className="w-[230px] h-full overflow-hidden rounded-3xl shadow ring-8 ring-gray-600/10">
            <img src={slides[right2]} alt="right2" className="object-cover w-full h-full" />
          </div>
        </div>

        {/* Buttons */}
        <Button
          onClick={prev}
          className="absolute cursor-pointer left-[33%] top-1/2  bg-lime-200 rounded-full "
        >
          <ChevronLeft className="w-5 h-5 text-black" />
        </Button>
        <Button
          onClick={next}
          className="absolute cursor-pointer right-[33%] top-1/2  bg-lime-200 rounded-full "
        >
          <ChevronRight className="w-5 h-5 text-black" />
        </Button>
      </div>
</section>
     
  );
}

export default HeroSection;
