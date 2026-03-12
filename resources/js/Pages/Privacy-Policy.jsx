import { FooterSection } from '@/components/ui/footer-section'
import { SimpleHeader } from '@/components/ui/simple-header'
import React from 'react'

export default function PrivacyPolicy() {
  return (
    <div className="min-h-screen ">
      <SimpleHeader />

    
      <div className="w-full bg-gradient-to-b from-[#f5fff7] via-[#f5f6ff] to-[#e5ffe9] mt-6">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
          <h1 className="text-3xl sm:text-4xl font-semibold text-gray-900 tracking-tight">
            Privacy Policy
          </h1>
          <p className="mt-3 text-sm sm:text-base text-gray-600 max-w-xl mx-auto">
            These terms govern how we collect, use, and protect your information while
            using HydroNew.
          </p>
        </div>
      </div>


      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 pb-16">
        <div className="bg-white rounded-2xl shadow-[0_18px_60px_rgba(15,23,42,0.08)]">
          <div className="px-6 sm:px-10 py-8 sm:py-10">
            
            {/* 1. Introduction */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                1. Introduction
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-3">
                Welcome to HydroNew. Your privacy is important to us. This Privacy Policy explains
                how our system collects, uses, stores, and protects your information when you use
                our platform.
              </p>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                HydroNew is an AI-driven IoT hydroponics monitoring system designed to assist users
                in managing and monitoring hydroponic crop production through data analytics and
                remote access. By using this system, you agree to the practices described in this
                Privacy Policy.
              </p>
            </section>

            {/* 2. Information We Collect */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                2. Information We Collect
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-4">
                Our system may collect the following types of information:
              </p>

              {/* 2.1 Personal Information */}
              <div className="mb-5">
                <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-2">
                  2.1 Personal Information
                </h3>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  When users interact with the system, we may collect personal information such as:
                </p>
                <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>Name</li>
                  <li>Email address</li>
                  <li>Account credentials</li>
                  <li>User role or profile information</li>
                </ul>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  This information is collected when users register, log in, or interact with system
                  features.
                </p>
              </div>

              {/* 2.2 System and Usage Data */}
              <div className="mb-5">
                <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-2">
                  2.2 System and Usage Data
                </h3>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  The system may also collect non-personal information such as:
                </p>
                <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>Device information</li>
                  <li>Browser type</li>
                  <li>IP address</li>
                  <li>System activity logs</li>
                  <li>Usage statistics</li>
                </ul>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  These data help improve system functionality and performance.
                </p>
              </div>

              {/* 2.3 Sensor and Environmental Data */}
              <div>
                <h3 className="text-sm sm:text-base font-semibold text-gray-900 mb-2">
                  2.3 Sensor and Environmental Data
                </h3>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                  Since HydroNew integrates IoT technologies, the system collects environmental data
                  from sensors, including:
                </p>
                <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                  <li>Temperature</li>
                  <li>Humidity</li>
                  <li>Water quality indicators</li>
                  <li>Nutrient levels</li>
                </ul>
                <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                  These data are used solely for monitoring hydroponic crop conditions and improving
                  system recommendations.
                </p>
              </div>
            </section>

            {/* 3. How We Use the Information */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                3. How We Use the Information
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                The collected information is used for the following purposes:
              </p>
              <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                <li>To provide and maintain system functionality</li>
                <li>To monitor hydroponic system performance</li>
                <li>To generate AI-based recommendations for crop management</li>
                <li>To improve system usability and performance</li>
                <li>To ensure system security and prevent unauthorized access</li>
              </ul>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                We only use the collected data for purposes directly related to the operation of the
                system.
              </p>
            </section>

            {/* 4. Data Storage and Security */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                4. Data Storage and Security
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                We implement appropriate technical and organizational measures to protect user
                information from unauthorized access, disclosure, alteration, or destruction.
              </p>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                Security measures may include:
              </p>
              <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                <li>Secure authentication systems</li>
                <li>Access control for authorized users</li>
                <li>Encrypted data transmission</li>
                <li>Regular system monitoring</li>
              </ul>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                However, while we strive to protect your data, no digital system can guarantee
                absolute security.
              </p>
            </section>

            {/* 5. Data Sharing and Disclosure */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                5. Data Sharing and Disclosure
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                HydroNew does not sell, rent, or trade personal information to third parties.
              </p>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                Information may only be shared under the following circumstances:
              </p>
              <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1">
                <li>When required by law or legal process</li>
                <li>When necessary to protect the security and integrity of the system</li>
                <li>For academic research purposes, where data may be anonymized</li>
              </ul>
            </section>

            {/* 6. User Rights */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                6. User Rights
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                Users have the right to:
              </p>
              <ul className="list-disc pl-5 text-sm sm:text-base text-gray-700 space-y-1 mb-2">
                <li>Access their personal information stored in the system</li>
                <li>Request correction of inaccurate data</li>
                <li>Request deletion of their account information where applicable</li>
              </ul>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                Requests regarding personal data may be submitted through the system administrators.
              </p>
            </section>

            {/* 7. Cookies and Tracking Technologies */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                7. Cookies and Tracking Technologies
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                The system may use cookies or similar technologies to enhance user experience,
                maintain login sessions, and analyze system usage.
              </p>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                Users may disable cookies through their browser settings, but some features of the
                system may not function properly.
              </p>
            </section>

            {/* 8. Changes to This Privacy Policy */}
            <section className="mb-8">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                8. Changes to This Privacy Policy
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                This Privacy Policy may be updated from time to time to reflect system improvements
                or legal requirements. Any changes will be posted on this page with the updated
                revision date.
              </p>
            </section>

            {/* 9. Contact Information */}
            <section>
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 mb-3">
                9. Contact Information
              </h2>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-2">
                If you have questions or concerns regarding this Privacy Policy, you may contact the
                system developers through the provided contact details on the platform.
              </p>
              <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
                By using HydroNew, you acknowledge that you have read and understood this Privacy
                Policy.
              </p>
            </section>
          </div>
        </div>
      </main>
      <FooterSection />
    </div>
  )
}
