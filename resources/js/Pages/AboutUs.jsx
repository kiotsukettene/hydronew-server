"use client"
import { Download } from "lucide-react"
import { SimpleHeader } from "@/components/ui/simple-header"
import { Button } from "@/components/ui/button"

export default function AboutUs() {
  const handleDownload = () => {
    window.open("/downloads/about-us-brochure.pdf", "_blank")
  }

  return (
    <div className="bg-white">
      {/* Header */}
      <SimpleHeader />

      {/* Hero Section */}
      <section className="relative w-full overflow-hidden">
        {/* Background Image with Overlay */}
        <div className="relative w-full">
          <img
            src="/images/about-us-bg.svg"
            alt="Sustainable technology landscape"
            className="w-full h-auto object-cover object-center p-4 sm:p-6 md:p-8 lg:p-10 pt-16 sm:pt-18 md:pt-20 lg:pt-24"
          />

{/* Download App Card */}
<div className="absolute top-18 right-5 sm:top-28 sm:right-9 md:top-32 md:right-13 lg:top-40 lg:right-16 xl:top-40 xl:right-20 z-20 bg-white/90 backdrop-blur-md rounded-lg sm:rounded-xl md:rounded-2xl shadow-md p-1.5 sm:p-3 md:p-4 flex flex-col items-start gap-1 sm:gap-2 md:gap-3 max-w-[80px] sm:max-w-[140px] md:max-w-[160px]">
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

{/* Hero Content */}
<div className="absolute inset-0 flex items-center justify-start px-3 sm:px-6 md:px-8 lg:px-12 xl:px-20 py-6 sm:py-10 md:py-12">
  <div className="relative p-4 sm:p-6 md:p-8 lg:p-10 xl:p-12 rounded-xl sm:rounded-2xl md:rounded-3xl top-6 sm:top-10 md:top-14 lg:top-17 sm:bg-[#6A9840]/26 w-full max-w-full sm:max-w-lg md:max-w-2xl lg:max-w-3xl">
    {/* Content Container */}
    <div className="text-white">
      {/* Badge */}
      <div className="inline-block mb-3 sm:mb-4 md:mb-6 lg:mb-8">
        <div className="bg-black/70 text-white rounded-full px-2 sm:px-3 md:px-4 py-1 sm:py-1.5 md:py-2 flex items-center gap-1 sm:gap-2">
          <div className="w-1 h-1 sm:w-2 sm:h-2 bg-white rounded-full"></div>
          <span className="text-xs font-medium">Who We Are</span>
        </div>
      </div>

      {/* Heading */}
      <h2 className="text-xs sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl mb-3 sm:mb-4 md:mb-5 lg:mb-6 leading-tight drop-shadow-lg text-balance">
        Blending Technology
        <br />
        with Sustainability
      </h2>

      {/* Description */}
      <p className="text-xs sm:text-sm md:text-base text-white/90 leading-relaxed drop-shadow-md max-w-full sm:max-w-md">
        We are Computer Science students committed to merging AI, IoT, and eco-technology to create sustainable farming solutions for the future.
      </p>
    </div>
  </div>
</div>
        </div>
      </section>

      {/* Mission & Vision Section */}
      <section className="bg-white py-8 sm:py-10 md:py-12 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-20">
        {/* Heading */}
        <h3 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-gray-900 mb-8 sm:mb-10 md:mb-12 text-left">
          What Inspires Our Journey
        </h3>

        {/* Container */}
        <div className="max-w-8xl text-left">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-14 xl:gap-20">

            {/* Mission Card */}
            <div className="bg-[#6E9A7F] rounded-2xl sm:rounded-3xl p-6 sm:p-7 md:p-8 text-white shadow-lg hover:scale-[1.02] transition-transform duration-300 h-[180px] sm:h-[200px] md:h-[220px] lg:h-[240px] xl:h-[260px] flex flex-col justify-center">
              <h4 className="text-2xl sm:text-3xl md:text-4xl text-center mb-3 sm:mb-4 pb-2 sm:pb-3">
                Our Mission
              </h4>
              <p className="text-xs sm:text-sm leading-snug opacity-90 text-center sm:text-left px-4 sm:px-6 md:px-8">
                To turn wastewater into a renewable resource that powers hydroponic farming, promoting food security and environmental resilience.
              </p>
            </div>

            {/* Vision Card */}
            <div className="bg-[#6E9A7F] rounded-2xl sm:rounded-3xl p-6 sm:p-7 md:p-8 text-white shadow-lg hover:scale-[1.02] transition-transform duration-300 h-[200px] sm:h-[220px] md:h-[240px] lg:h-[260px] xl:h-[280px] flex flex-col justify-center">
              <h4 className="text-2xl sm:text-3xl md:text-4xl text-center mb-3 sm:mb-4 pb-2 sm:pb-3">
                Our Vision
              </h4>
              <p className="text-xs sm:text-sm leading-snug opacity-90 text-center sm:text-left px-4 sm:px-6 md:px-15">
                To lead in building AI-driven agricultural systems that empower<br/>
                Philippines communities—and beyond—with sustainable water and<br />
                farming innovations.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Team Members Section */}
      <section className="bg-white py-8 sm:py-10 md:py-12 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-20">
        <div className="max-w-8xl">
          {/* Section Title */}
          <h3 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl text-gray-900 mb-8 sm:mb-12 md:mb-16 mt-6 sm:mt-8 md:mt-10 text-left">
            Meet the dedicated team
          </h3>

          {/* Team Grid */}
          <div className="flex flex-wrap justify-center gap-4 sm:gap-6 md:gap-8 mx-auto max-w-4xl">
            {[
              {
                name: "Joshua Gabriel Dantes",
                role: "Project Manager / Backend Developer",
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
              <div key={index} className="flex flex-col items-center text-center relative w-[140px] sm:w-[160px] md:w-[180px] lg:w-[200px]">
                {/* Profile Image Container */}
                <div className="w-32 h-32 sm:w-36 sm:h-36 md:w-40 md:h-40 lg:w-44 lg:h-44 overflow-hidden rounded-xl sm:rounded-2xl shadow-lg">
                  <img
                    src={member.image}
                    alt={member.name}
                    className="w-full h-full object-cover"
                  />
                </div>

                {/* Glass Morphism Info Box */}
                <div className="absolute bottom-0 bg-black/80 backdrop-blur-md text-white rounded-b-xl sm:rounded-b-2xl rounded-t-none p-1.5 sm:p-2 w-full max-w-[140px] sm:max-w-[160px] md:max-w-[180px] lg:max-w-[200px] shadow-xl">
                  <h4 className="font-semibold text-[10px] sm:text-xs mb-0.5 leading-tight">
                    {member.name}
                  </h4>
                  <p className="text-[9px] sm:text-[10px] text-gray-300 leading-tight">
                    {member.role}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Acknowledgment Section */}
      <section className="bg-[#6E9A7F] text-white py-8 sm:py-10 mt-8 sm:mt-10 md:mt-12 rounded-2xl sm:rounded-3xl mx-4 sm:mx-6 md:mx-8 lg:mx-12 xl:mx-20 mb-2 shadow-md">
        <div className="max-w-5xl mx-auto text-center px-4 sm:px-6">
          <h3 className="text-xl sm:text-2xl md:text-3xl font-bold mb-3 sm:mb-4">Acknowledgment</h3>
          <p className="text-xs sm:text-sm md:text-base opacity-95 leading-relaxed sm:leading-snug">
            Hydronew is developed as part of our undergraduate thesis in Bachelor of Science in Computer
            Science at the University of Caloocan City.
          </p>
        </div>
      </section>
    </div>
  );
}
