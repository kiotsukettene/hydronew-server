import React, { useMemo, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import IPhoneMockup from './iphone-mockup'
import { Button } from './button'

function HowItWorks() {
  const slides = useMemo(
    () => [
      { src: '/images/monitor.png', title: 'Real‑time monitoring', desc: 'Live readings and alerts to keep your system healthy.' },
      { src: '/images/hydroponics.png', title: 'Hydroponics control', desc: 'Control pumps, lights and nutrients with a tap.' },
      { src: '/images/filtration.png', title: 'Water filtration', desc: 'Track filters and water quality over time.' },
      { src: '/images/dashboard.png', title: 'Dashboard overview', desc: 'Quick status for pH, EC, water level, and more.' },
      { src: '/images/account.png', title: 'Account & settings', desc: 'Manage preferences, profiles and notifications.' }
    ],
    []
  )

  const [index, setIndex] = useState(0)
  const total = slides.length
  const next = () => setIndex((prev) => (prev + 1) % total)
  const prev = () => setIndex((prev) => (prev - 1 + total) % total)
  const secondIndex = (index + 1) % total

  return (
    <section className="w-full py-6 md:py-8 bg-white relative">
      

      <div className="max-w-7xl h-full mx-auto px-2 md:px-4">
        <div className="text-center mb-6">
          <h2 className="text-4xl md:text-5xl font-semibold text-[#1b1b1b]">
            How it works
          </h2>
          <p className="mt-4 text-gray-700 max-w-2xl mx-auto">
            See key parts of the HydroNew app in action.
          </p>
        </div>

        {/* Carousel */}
        <div className="relative md:p-1">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            {/* Card A */}
            <div className="rounded-3xl bg-[#FFF3DB] p-3 md:p-4 overflow-hidden flex flex-col items-center text-center">
              <div className="scale-[0.68]">
                <IPhoneMockup
                  color="space-black"
                  wallpaper={slides[index].src}
                  wallpaperFit="cover"
                  wallpaperPosition="center"
                />
              </div>
              <h3 className="text-xl font-semibold text-[#1b1b1b]">
                {slides[index].title}
              </h3>
              <p className="mt-2 text-gray-700 max-w-sm">{slides[index].desc}</p>
            </div>

            {/* Card B */}
            <div className="rounded-3xl bg-[#FFF3DB] p-3 md:p-4 overflow-hidden flex flex-col items-center text-center">
              <div className="scale-[0.68]">
                <IPhoneMockup
                  color="space-black"
                  wallpaper={slides[secondIndex].src}
                  wallpaperFit="cover"
                  wallpaperPosition="center"
                />
              </div>
              <h3 className="text-xl font-semibold text-[#1b1b1b]">
                {slides[secondIndex].title}
              </h3>
              <p className="mt-2 text-gray-700 max-w-sm">{slides[secondIndex].desc}</p>
            </div>
          </div>

          {/* Controls */}
          <Button
            onClick={prev}
            className="absolute left-1 md:left-4 top-1/2 -translate-y-1/2 bg-black text-white rounded-full"
          >
            <ChevronLeft className="w-5 h-5" />
          </Button>
          <Button
            onClick={next}
            className="absolute right-1 md:right-4 top-1/2 -translate-y-1/2 bg-black text-white rounded-full"
          >
            <ChevronRight className="w-5 h-5" />
          </Button>

          {/* Dots */}
          <div className="flex items-center justify-center gap-2 mt-3">
            {slides.map((_, i) => (
              <button
                key={i}
                aria-label={`Go to slide ${i + 1}`}
                onClick={() => setIndex(i)}
                className={`w-1.5 h-1.5 rounded-full transition-colors ${
                  i === index ? 'bg-black' : 'bg-black/30'
                }`}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}

export default HowItWorks
