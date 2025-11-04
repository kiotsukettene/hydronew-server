"use client"
import { TimelineContent } from "@/components/ui/timeline-animation"
import { Zap } from "lucide-react"
import { useRef } from "react"
// import HydroNewSystemOverview from "./database-with-rest-api"

export default function AboutSection2() {
  const heroRef = useRef(null)

  const revealVariants = {
    visible: (i) => ({
      y: 0,
      opacity: 1,
      filter: "blur(0px)",
      transition: {
        delay: i * 1.5,
        duration: 0.7,
      },
    }),
    hidden: {
      filter: "blur(10px)",
      y: 40,
      opacity: 0,
    },
  }

  const textVariants = {
    visible: (i) => ({
      filter: "blur(0px)",
      opacity: 1,
      transition: {
        delay: i * 0.3,
        duration: 0.7,
      },
    }),
    hidden: {
      filter: "blur(10px)",
      opacity: 0,
    },
  }

  return (
    <section className="pt-24 ">
      <div className="max-w-7xl mx-auto" ref={heroRef}>
        <div className="mb-8">
          <header
            className="mb-10 flex flex-col gap-6 border-b border-neutral-900/10 pb-6 transition-colors duration-500 md:flex-row md:items-end md:justify-between dark:border-white/10">
            <div className="flex flex-col gap-2">
              <span
                className="text-md uppercase tracking-[0.35em] text-neutral-500 transition-colors duration-500 dark:text-white/40">
                ABOUT
              </span>
              <h2 style={{ fontFamily: '"Viaoda Libre", serif' }}
                className="text-3xl font-black tracking-tight text-neutral-900 transition-colors duration-500 md:text-5xl dark:text-white">
                Why We Created HydroNew          
                </h2>
            </div>

          </header>
        </div>


        {/* Main Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
          {/* Left Side - Main Text */}

          <div className="flex gap-6">
            {/* Right side - Content */}
            <div className="flex-1 bg-neutral-800 p-8 rounded-lg ">

              <TimelineContent
                as="h1"
                animationNum={0}
                timelineRef={heroRef}
                customVariants={revealVariants}
                className="sm:text-4xl text-2xl md:text-5xl !leading-[110%] font-semibold text-neutral-100 mb-8"
              >
                We strive to create{" "}
                <TimelineContent
                  as="span"
                  animationNum={2}
                  timelineRef={heroRef}
                  customVariants={textVariants}
                  className="text-green-400 border-2 border-green-300 inline-block xl:h-16 border-dotted px-2 rounded-md"
                >
                  sustainable
                </TimelineContent>{" "}
                farming solutions for {" "}
                <TimelineContent
                  as="span"
                  animationNum={3}
                  timelineRef={heroRef}
                  customVariants={textVariants}
                  className="text-emerald-600 border-2 border-emerald-500 inline-block xl:h-16 border-dotted px-2 rounded-md"
                >
                  future generations.
                </TimelineContent>
              </TimelineContent>


            </div>
          </div>


          {/* Right Side - Supporting Text */}
          <div className="space-y-4">
            <p className="text-2xl text-black leading-relaxed">
              HYDRONEW HAS DEVELOPED AN AI-ENABLED
              WASTEWATER TREATMENT SYSTEM THAT TRANSFORMS
              GREYWATER INTO SAFE, REUSABLE WATER FOR
              HYDROPONICS, HELPING FARMERS GROW FRESH
              LETTUCE WHILE CONSERVING OUR NATURAL
              RESOURCES.
            </p>
          </div>

          {/* <HydroNewSystemOverview/> */}

        </div>

      </div>
    </section>
  )
}
