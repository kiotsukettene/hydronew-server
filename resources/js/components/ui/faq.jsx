import React from 'react'
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from './accordion'

const FAQ = () => {
  const items = [
    {
      q: 'What is Hydronew?',
      a: (
        <>
          Hydronew is a smart, all-in-one water recycling system. It takes organic household
          greywater (like water from washing vegetables or rice), cleans it using a multi-stage
          process, and then uses that safe, treated water to automatically irrigate an attached
          hydroponic garden.
        </>
      )
    },
    {
      q: 'How does it actually clean the water?',
      a: (
        <>
          Hydronew uses a three-stage process to ensure the water is safe for your plants:
          <ul className="list-disc pl-5 mt-2 space-y-1">
            <li>
              <span className="font-medium">Stage 1: Natural Filtration</span> — The water first passes
              through a filter of sand, gravel, and anthracite to remove physical particles and
              sediment.
            </li>
            <li>
              <span className="font-medium">Stage 2: Bio‑Treatment (MFC)</span> — In a Microbial Fuel Cell
              (MFC), beneficial microorganisms naturally consume organic waste, cleaning the water and
              generating a small amount of electricity.
            </li>
            <li>
              <span className="font-medium">Stage 3: UV Sterilization</span> — Finally, a UV filter
              sterilizes the water, eliminating 99.9% of bacteria and pathogens.
            </li>
          </ul>
        </>
      )
    },
    {
      q: 'Is the treated water really safe for growing food?',
      a: (
        <>
          Yes. Safety is our number one priority. The multi‑stage process is designed to meet
          agricultural irrigation standards for safe use with edible crops.
        </>
      )
    },
    {
      q: 'What kind of water can I use? What is "organic greywater"?',
      a: (
        <>
          Hydronew is designed for organic greywater, such as:
          <ul className="list-disc pl-5 mt-2 space-y-1">
            <li>Washing rice or vegetables</li>
          </ul>
          <p className="mt-2">
            It is <span className="font-medium">not</span> designed to treat blackwater (from toilets)
            or water containing harsh chemicals like laundry detergent, bleach, or shower soap.
          </p>
        </>
      )
    },
    {
      q: 'Is Hydronew available to buy?',
      a: (
        <>
          Hydronew is currently a university‑backed research prototype. We are in the final stages of
          development and testing to ensure effectiveness, safety, and reliability. Interested in our
          progress? Sign up for our newsletter to receive updates, test results, and future
          availability.
        </>
      )
    },
    {
      q: 'Do I need any technical experience to use it?',
      a: (
        <>
          No. The system is fully automated. A companion mobile app provides a simple dashboard to see
          system status, check on your plants, and receive alerts—no technical expertise required.
        </>
      )
    }
  ]

  return (
    <section id="faq" className="w-full py-12 sm:py-16 lg:py-20">
      <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8 md:mb-10">
          <span className="inline-block rounded-full border px-3 py-1 text-xs font-medium tracking-wide text-neutral-600">Questions</span>
          <h2 className="mt-4 text-2xl sm:text-3xl md:text-4xl font-serif tracking-tight text-neutral-900">Got questions? We've got answers</h2>
          <p className="mt-3 text-sm sm:text-base text-neutral-600 px-4">Explore our frequently asked questions to find the information you need about our services and process.</p>
        </div>

        <div className="relative rounded-2xl border border-neutral-200 shadow-[0_10px_40px_rgba(0,0,0,0.06)] overflow-hidden">
          <div className="pointer-events-none absolute inset-x-0 -top-20 h-40 bg-gradient-to-b from-rose-200/50 to-transparent" />
          <Accordion type="single" collapsible className="relative divide-y">
            {items.map((item, idx) => (
              <AccordionItem key={idx} value={`item-${idx}`} className="group border-neutral-200">
                <AccordionTrigger className="px-4 md:px-6 rounded-lg hover:bg-black hover:text-white transition-colors">
                  {item.q}
                </AccordionTrigger>
                <AccordionContent className="px-4 md:px-6 text-neutral-600">
                  {item.a}
                </AccordionContent>
              </AccordionItem>
            ))}
          </Accordion>
        </div>
      </div>
    </section>
  )
}

export default FAQ
