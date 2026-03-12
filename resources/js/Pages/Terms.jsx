import { FooterSection } from '@/components/ui/footer-section'
import { SimpleHeader } from '@/components/ui/simple-header'
import React from 'react'

export default function Terms() {
  return (
     <div className="min-h-screen ">
         <SimpleHeader />
   
       
         <div className="w-full bg-gradient-to-b from-[#f5fff7] via-[#f5f6ff] to-[#e5ffe9] mt-6">
           <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
             <h1 className="text-3xl sm:text-4xl font-semibold text-gray-900 tracking-tight">
               Terms and Conditions
             </h1>
             <p className="mt-3 text-sm sm:text-base text-gray-600 max-w-xl mx-auto">
              Guidelines and responsibilities for the proper use of the HydroNew system.
             </p>
           </div>
         </div>
   
   
         <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 pb-16">
           <div className="bg-white rounded-2xl shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
             <div className="px-6 sm:px-10 py-8 sm:py-10">
               
              {/* 1. Acceptance of Terms */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  1. Acceptance of Terms
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-3">
                  By accessing and using the HydroNew system, you agree to comply with and be bound
                  by these Terms and Conditions. If you do not agree with any part of these terms,
                  you should not use the system.
                 </p>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  These terms apply to all users who access or interact with the platform, including
                  administrators, staff, and general users.
                 </p>
               </section>
   
              {/* 2. Purpose of the System */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  2. Purpose of the System
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-4">
                  HydroNew is an AI-driven IoT hydroponics monitoring system developed for
                  educational and research purposes. The platform is designed to assist users in
                  monitoring environmental conditions, managing hydroponic crop production, and
                  analyzing data collected from IoT sensors.
                 </p>
   
                {/* 2.1 Intended Use */}
                 <div className="mb-5">
                   <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-2">
                    2.1 Intended Use
                   </h3>
                   <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                    Users agree to use the system only for its intended purposes and in accordance
                    with applicable laws and regulations.
                   </p>
                 </div>
   
               </section>
   
              {/* 3. User Accounts */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  3. User Accounts
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  To access certain features of the system, users may be required to create an
                  account. Users are responsible for:
                 </p>
                 <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>Providing accurate and complete registration information</li>
                  <li>Maintaining the confidentiality of their account credentials</li>
                  <li>
                    Ensuring that all activities conducted under their account comply with these
                    Terms and Conditions
                  </li>
                 </ul>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  The system administrators reserve the right to suspend or terminate accounts that
                  violate these terms.
                 </p>
               </section>
   
              {/* 4. Acceptable Use */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  4. Acceptable Use
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  Users agree to use the system responsibly and must not:
                 </p>
                 <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1">
                  <li>Attempt to gain unauthorized access to the system or its databases</li>
                  <li>Interfere with the normal operation of the system</li>
                  <li>Upload or transmit malicious software, viruses, or harmful code</li>
                  <li>Use the system for illegal, harmful, or unethical activities</li>
                  <li>Misuse the system data or attempt to manipulate sensor data</li>
                 </ul>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mt-2">
                  Any violation of these rules may result in restricted access or account
                  termination.
                </p>
               </section>
   
              {/* 5. Data Accuracy and System Limitations */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  5. Data Accuracy and System Limitations
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  HydroNew provides monitoring data and AI-generated recommendations based on sensor
                  inputs and system algorithms. While the system aims to provide reliable insights,
                  users acknowledge that:
                 </p>
                 <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>
                    Sensor data may contain inaccuracies due to environmental conditions or hardware
                    limitations.
                  </li>
                  <li>
                    AI-generated recommendations are intended to assist decision-making but should
                    not replace professional agricultural judgment.
                  </li>
                 </ul>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  The developers are not responsible for any agricultural losses or damages
                  resulting from the use of the system.
                 </p>
               </section>
   
              {/* 6. Intellectual Property */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  6. Intellectual Property
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  All system components, including software, design, system architecture, and
                  documentation, are the intellectual property of the system developers.
                 </p>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  Users may not copy, reproduce, modify, distribute, or commercially exploit any
                  part of the system without permission from the developers.
                 </p>
               </section>
   
              {/* 7. System Availability */}
               <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  7. System Availability
                 </h2>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  The developers aim to maintain system availability and performance; however,
                  uninterrupted access cannot be guaranteed.
                </p>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  The system may occasionally experience:
                </p>
                <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>Maintenance updates</li>
                  <li>Technical issues</li>
                  <li>Temporary downtime</li>
                </ul>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  The developers are not liable for any losses resulting from system interruptions.
                 </p>
               </section>
   
              {/* 8. Modification of Terms */}
              <section className="mb-8">
                 <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  8. Modification of Terms
                 </h2>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  These Terms and Conditions may be updated or modified when necessary to reflect
                  system improvements or policy changes. Users will be informed of significant
                  updates through the platform.
                 </p>
                 <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  Continued use of the system after changes are posted constitutes acceptance of the
                  updated terms.
                 </p>
               </section>

              {/* 9. Termination of Access */}
              <section className="mb-8">
                <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  9. Termination of Access
                </h2>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  The system administrators reserve the right to suspend or terminate access to
                  users who violate these Terms and Conditions or misuse the system.
                </p>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  Termination may occur without prior notice if necessary to protect system security
                  or integrity.
                </p>
              </section>

              {/* 10. Contact Information */}
              <section>
                <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                  10. Contact Information
                </h2>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  For questions or concerns regarding these Terms and Conditions, users may contact
                  the system developers through the contact information provided on the platform.
                </p>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  By using HydroNew, you acknowledge that you have read, understood, and agreed to
                  these Terms and Conditions.
                </p>
              </section>
             </div>
           </div>
         </main>
         <FooterSection />
       </div>
  )
}
