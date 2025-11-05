"use client"
import { Download } from "lucide-react"
import { SimpleHeader } from "@/components/ui/simple-header"
import { Button } from "@/components/ui/button"
import { FooterSection } from "@/components/ui/footer-section"
import { router } from '@inertiajs/react'

export default function AboutUs() {
  const handleDownload = () => {
    router.visit('/download')
  }

  return (
    <div className="bg-white px-4 sm:px-6 md:px-12 lg:px-24 xl:px-48 py-4 sm:py-6 md:py-8 lg:py-10">
      {/* Header */}
      <SimpleHeader />

      {/* Hero Section */}
      <section className="relative w-full flex justify-center items-center overflow-hidden mb-8 sm:mb-12 md:mb-16 lg:mb-20">
        {/* Background Image Container - acts as boundary */}
        <div className="relative w-full max-w-7xl overflow-hidden">
          <img
            src="/images/about-us-bg.svg"
            alt="Sustainable technology landscape"
            className="w-full h-auto object-cover object-center p-2 sm:p-3 md:p-4 lg:p-6 pt-6 sm:pt-8 md:pt-10 lg:pt-12"
          />

          {/* Download App Card - positioned within image bounds */}
          <div className="absolute top-12 right-4 sm:top-20 sm:right-10 md:top-24 md:right-16 lg:top-28 lg:right-20 z-20 bg-white/90 backdrop-blur-md rounded-lg sm:rounded-xl md:rounded-2xl shadow-md p-1.5 sm:p-3 md:p-4 flex flex-col items-start gap-1 sm:gap-2 md:gap-3 max-w-[80px] sm:max-w-[140px] md:max-w-[160px]">
            {/* Text - Hidden on mobile, visible on sm and up */}
            <div className="hidden sm:block">
              <p className="text-xs font-semibold text-gray-700">Download</p>
              <p className="text-sm font-bold text-gray-900">Our App</p>
            </div>

            {/* Download Button - Always visible */}
            <Button
              variant="ghost"
              size="sm"
              onClick={handleDownload}
              className="bg-white text-black border border-black rounded-full px-1.5 sm:px-3 md:px-4 py-1 sm:py-1.5 md:py-2 flex items-center gap-1 sm:gap-2 hover:bg-gray-200 hover:text-black w-full justify-center transition-all duration-200 text-xs"
            >
              <Download className="w-3 h-3 sm:w-4 sm:h-4" />
              <span className="hidden sm:inline">Download</span>
            </Button>
          </div>

          {/* Hero Content - positioned within image bounds */}
          <div className="absolute inset-0 flex items-center justify-start p-3 sm:p-6 md:p-8 lg:p-12 xl:p-16">
            <div className="relative p-2 sm:p-5 md:p-6 lg:p-8 rounded-xl sm:rounded-2xl md:rounded-3xl bg-[#6A9840]/20 sm:bg-[#6A9840]/26 max-w-[95%] sm:max-w-lg md:max-w-xl lg:max-w-2xl">
              {/* Content Container */}
              <div className="text-white">
                {/* Badge */}
                <div className="inline-block mb-2 sm:mb-4 md:mb-6 lg:mb-8">
                  <div className="bg-black/70 text-white rounded-full px-2 sm:px-3 md:px-4 py-1 sm:py-1.5 md:py-2 flex items-center gap-1 sm:gap-2">
                    <div className="w-1 h-1 sm:w-2 sm:h-2 bg-white rounded-full"></div>
                    <span className="text-[10px] sm:text-xs font-medium">Who We Are</span>
                  </div>
                </div>

                {/* Heading */}
                <h2 className="text-base sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl mb-2 sm:mb-4 md:mb-5 lg:mb-6 leading-tight  text-balance font-semibold">
                  Blending Technology
                  <br />
                  with Sustainability
                </h2>

                {/* Description */}
                <p className="text-[10px] sm:text-sm md:text-base text-white/90 leading-relaxed max-w-full sm:max-w-md">
                  We are Computer Science students committed to merging AI, IoT, and eco-technology to create sustainable farming solutions for the future.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Mission & Vision Section */}
      <section className="bg-white py-8 sm:py-10 md:py-12 lg:py-16 px-0 sm:px-2 md:px-4 lg:px-8">
        {/* Heading */}
        <div className="text-center mb-8 sm:mb-10 md:mb-12 lg:mb-14">
          {/* <span className="inline-block rounded-full border px-3 py-1 text-xs font-medium tracking-wide text-neutral-600">Questions</span> */}
          <h2 className="text-3xl md:text-4xl lg:text-5xl font-medium tracking-tight text-neutral-900 font-serif">What inspires our journey</h2>
          <p className="mt-3 sm:mt-4 text-sm sm:text-base text-neutral-600">Our Commitment to Growth and Innovation.</p>
        </div>

        {/* Container */}
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 md:gap-8 lg:gap-10 xl:gap-12">

            {/* Mission Card */}
            <div className="bg-[#6E9A7F] rounded-xl sm:rounded-2xl md:rounded-3xl p-5 sm:p-6 md:p-7 lg:p-8 text-white shadow-lg hover:scale-[1.02] transition-transform duration-300 min-h-[200px] sm:min-h-[220px] md:min-h-[240px] lg:min-h-[260px] flex flex-col justify-center">
              <h4 className="text-xl sm:text-2xl md:text-3xl lg:text-4xl text-center mb-3 sm:mb-4 lg:mb-5 font-semibold">
                Our Mission
              </h4>
              <p className="font-light text-sm sm:text-base md:text-lg leading-relaxed opacity-95 text-center px-2 sm:px-4 md:px-6">
                To turn wastewater into a renewable resource that powers hydroponic farming, promoting food security and environmental resilience.
              </p>
            </div>

            {/* Vision Card */}
            <div className="bg-[#6E9A7F] rounded-xl sm:rounded-2xl md:rounded-3xl p-5 sm:p-6 md:p-7 lg:p-8 text-white shadow-lg hover:scale-[1.02] transition-transform duration-300 min-h-[200px] sm:min-h-[220px] md:min-h-[240px] lg:min-h-[260px] flex flex-col justify-center">
              <h4 className="text-xl sm:text-2xl md:text-3xl lg:text-4xl text-center mb-3 sm:mb-4 lg:mb-5 font-semibold">
                Our Vision
              </h4>
              <p className="font-light text-sm sm:text-base md:text-lg leading-relaxed opacity-95 text-center px-2 sm:px-4 md:px-6">
                To lead in building AI-driven agricultural systems that empower Philippines communities—and beyond—with sustainable water and farming innovations.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Team Members Section */}
      <section className="bg-white py-8 sm:py-10 md:py-12 lg:py-16 px-0 sm:px-2 md:px-4 lg:px-8">
        <div className="max-w-7xl mx-auto">
          {/* Heading */}
        <div className="text-center mb-8 sm:mb-10 md:mb-12 lg:mb-14">
          <span className="inline-block rounded-full border px-3 py-1 text-xs font-medium tracking-wide text-neutral-600">The Developers</span>
          <h2 className="mt-4 sm:mt-5 text-3xl md:text-4xl lg:text-5xl font-medium tracking-tight text-neutral-900 font-serif">Meet the Team</h2>
          <p className="mt-3 sm:mt-4 text-sm sm:text-base text-neutral-600">The Minds Behind the Innovation.</p>
        </div>

          {/* Team Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 lg:gap-8 justify-items-center">
            {[
              {
                name: "Joshua Gabriel Dantes",
                role: "Project Manager",
                image: "/images/joshua.png"
              },
              {
                name: "Russell Kelvin Anthony Loreto",
                role: "Software Lead / Full Stack Developer",
                image: "/images/russell.png"
              },
              {
                name: "Raymond Palomares",
                role: "Research Lead / Database Administrator",
                image: "/images/raymond.png"
              },
              {
                name: "Marianne Celest T. Jerez",
                role: "Lead Frontend Developer / UI UX Designer",
                image: "/images/marianne.png"
              },
              {
                name: "Lyniel Aya-ay",
                role: "Lead IoT & Hardware Developer",
                image: "/images/lyniel.png"
              },
              {
                name: "B.J. Cabaat",
                role: "Quality Assurance Specialist",
                image: "/images/bj.png"
              },
              {
                name: "Calvin Ramboyong",
                role: "Frontend Developer",
                image: "/images/calvin.png"
              },
            ].map((member, index) => (
              <div key={index} className="flex flex-col items-center text-center relative w-full max-w-[150px] sm:max-w-[160px] md:max-w-[180px] lg:max-w-[200px]">
                {/* Profile Image Container */}
                <div className="w-full aspect-square overflow-hidden rounded-xl sm:rounded-2xl shadow-lg">
                  <img
                    src={member.image}
                    alt={member.name}
                    className="w-full h-full object-cover"
                  />
                </div>

                {/* Glass Morphism Info Box */}
                <div className="absolute  bottom-0 bg-black/80 backdrop-blur-md text-white rounded-b-xl sm:rounded-b-2xl rounded-t-none p-2 sm:p-2.5 md:p-3 w-full shadow-xl">
                  <h4 className="font-semibold text-[9px] sm:text-[10px] md:text-xs mb-0.5 leading-tight">
                    {member.name}
                  </h4>
                  <p className="text-[8px] sm:text-[9px] md:text-[10px] text-gray-300 leading-tight">
                    {member.role}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Acknowledgment Section */}
      <section className="bg-[#6E9A7F] text-white py-8 sm:py-10 md:py-12 lg:py-14 mt-8 sm:mt-12 md:mt-16 lg:mt-20 rounded-xl sm:rounded-2xl md:rounded-3xl mx-0 sm:mx-2 md:mx-4 lg:mx-8 mb-4 sm:mb-6 shadow-lg">
        <div className="max-w-4xl mx-auto text-center px-4 sm:px-6 md:px-8">
          <h3 className="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-semibold mb-4 sm:mb-5 md:mb-6">Acknowledgment</h3>
          <p className="text-sm sm:text-base md:text-lg lg:text-xl opacity-95 leading-relaxed font-light">
            Hydronew is developed as part of our undergraduate thesis in Bachelor of Science in Computer Science at the University of Caloocan City.
          </p>
        </div>
      </section>

      <FooterSection />

    </div>
  );
}
