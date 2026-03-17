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
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 md:gap-6 justify-items-center">
            {[
              {
                name: "Joshua Gabriel Dantes",
                role: "Project Manager",
                image: "/images/riel.png",
                linkedin: "https://www.linkedin.com/in/joshua-gabriel-p-dantes-230a99371/"
              },
              {
                name: "Russell Kelvin Anthony Loreto",
                role: "Software Lead / Full Stack Developer",
                image: "/images/russ.png",
                linkedin: "http://www.linkedin.com/in/russell-loreto"
              },
              {
                name: "Raymond Palomares",
                role: "Research Lead / Database Administrator",
                image: "/images/mon.png",
                linkedin: "http://www.linkedin.com/in/palomares-raymond"
              },
              {
                name: "Marianne Celest Jerez",
                role: "Lead Frontend Developer / UI UX Designer",
                image: "/images/celest.png",
                linkedin: "https://www.linkedin.com/in/jerez-marianne-celest/"
              },
              {
                name: "Lyniel Aya-ay",
                role: "Lead IoT & Hardware Developer",
                image: "/images/niel.png",
                linkedin: "https://www.linkedin.com/in/lyniel-cris-aya-ay/"
              },
              {
                name: "B.J. Cabaat",
                role: "Quality Assurance Specialist",
                image: "/images/bjj.png",
                linkedin: "https://www.linkedin.com/in/b-j-cabaat-a18664365"
              },
              {
                name: "Calvin Ramboyong",
                role: "Frontend Developer",
                image: "/images/calvs.png",
                linkedin: "https://www.linkedin.com/in/jhediael-calvin-ramboyong-b4573932b/"
              },
            ].map((member, index) => (
              <div
                key={index}
                className="w-full max-w-[190px] sm:max-w-[210px] md:max-w-[230px] lg:max-w-[250px] flex justify-center"
              >
                <div className="relative w-full ">
                  {/* Card */}
                  <div className="bg-[#dbdbdb] rounded-xl shadow-xl overflow-hidden w-full pt-6 pb-12">
                    {/* Profile Image */}
                    <div className="w-full sm:h-52 md:h-56 flex items-center justify-center">
                      <img
                        src={member.image}
                        alt={member.name}
                        className="h-full w-auto object-contain"
                      />
                    </div>
                  </div>

                  {/* Floating Info Card */}
                  <div className="absolute left-3 right-3 bottom-4 bg-white rounded-xl px-3 py-2.5 sm:px-4 sm:py-3 flex items-center justify-between gap-2">
                    <div className="text-left">
                      <h4 className="font-semibold text-xs sm:text-sm leading-tight text-gray-900">
                        {member.name}
                      </h4>
                      <p className="text-[10px] sm:text-xs text-gray-500 leading-tight">
                        {member.role}
                      </p>
                    </div>

                    {/* LinkedIn icon button */}
                    {member.linkedin && (
                      <a
                        href={member.linkedin}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={`${member.name} on LinkedIn`}
                        className="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-gray-200 bg-gray-50 text-[12px] font-semibold text-gray-800 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-colors"
                      >
                        in
                      </a>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Acknowledgment Section */}
      <section className="bg-gray-400 text-white py-8 sm:py-10 md:py-12 lg:py-14 mt-8 sm:mt-12 md:mt-16 lg:mt-20 rounded-xl sm:rounded-2xl md:rounded-3xl mx-0 sm:mx-2 md:mx-4 lg:mx-8 mb-4 sm:mb-6 ">
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
