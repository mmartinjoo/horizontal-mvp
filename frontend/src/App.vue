<script setup>
import { ref } from 'vue'

const email = ref('')
const isSubmitting = ref(false)
const showSuccess = ref(false)
const showError = ref(false)

// Replace these with your actual Google Form details
// Instructions:
// 1. Create a Google Form with one email field
// 2. Get the form URL and replace 'viewform' with 'formResponse'
// 3. Inspect the email field to find the entry number (e.g., entry.123456789)
const GOOGLE_FORM_ACTION = 'https://docs.google.com/forms/d/e/1FAIpQLScvh4EpaNhkV5fabMGkxOxJeVg6guD3b3X1jKgBJN1tiERsaA/formResponse'
const EMAIL_FIELD_NAME = 'entry.161718733' // e.g., 'entry.123456789'

const submitToGoogleForms = async () => {
  if (!email.value) return

  isSubmitting.value = true
  showError.value = false
  showSuccess.value = false

  try {
    // Create form data
    const formData = new FormData()
    formData.append(EMAIL_FIELD_NAME, email.value)

    // Submit to Google Forms using no-cors mode
    await fetch(GOOGLE_FORM_ACTION, {
      method: 'POST',
      mode: 'no-cors', // This is key for Google Forms
      body: formData
    })

    // Since no-cors doesn't return response, we assume success
    showSuccess.value = true
    email.value = ''

    // Track the conversion (optional)
    console.log('Waitlist signup successful')

  } catch (error) {
    console.error('Error submitting to Google Forms:', error)
    showError.value = true
  } finally {
    isSubmitting.value = false
  }
}

const handleSubmit = (e) => {
  e.preventDefault()
  submitToGoogleForms()
}
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Navigation Bar -->
    <nav class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
          <!-- Logo -->
          <div class="flex-shrink-0">
            <h1 class="text-2xl font-bold text-black">Horizontal</h1>
          </div>

          <!-- Navigation Links -->
          <div class="hidden md:block">
            <div class="ml-10 flex items-center space-x-12">
              <a href="#problem" class="text-gray-500 hover:text-gray-900 text-base font-medium transition-colors">
                Problem
              </a>
              <a href="#solution" class="text-gray-500 hover:text-gray-900 text-base font-medium transition-colors">
                Solution
              </a>
              <a href="#waitlist" class="text-gray-500 hover:text-gray-900 text-base font-medium transition-colors">
                Join Waitlist
              </a>
            </div>
          </div>

          <!-- CTA Button -->
          <div class="flex-shrink-0">
            <a href="#waitlist" class="bg-gray-900 text-white px-6 py-2.5 rounded-lg text-base font-medium hover:bg-gray-800 transition-colors inline-block">
              Join Waitlist
            </a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <main class="max-w-6xl mx-auto px-6 lg:px-8 pt-24 pb-16">
      <div class="text-center">
        <!-- Main Headline -->
        <h1 class="text-6xl lg:text-7xl font-bold text-black mb-8 leading-tight">
          The <span class="bg-gradient-to-r from-purple-500 via-purple-600 to-blue-500 bg-clip-text text-transparent">everything app</span>
          <br>
          for your entire team
        </h1>

        <!-- Subheading -->
        <div class="max-w-4xl mx-auto mb-16 space-y-2">
          <p class="text-xl lg:text-2xl text-gray-600">
            Stop wasting time switching between 5+ different apps.
          </p>
          <p class="text-xl lg:text-2xl text-gray-600">
            Connect all your tools and <span class="bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent font-semibold">search everything</span> in one place.
          </p>
        </div>

        <!-- CTA Button -->
        <div class="mb-16">
          <button class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-10 py-4 rounded-2xl text-xl font-semibold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
            Join the Waitlist →
          </button>
        </div>

        <!-- Social Proof -->
        <div class="flex items-center justify-center space-x-4 text-gray-500 mb-20">
          <div class="flex -space-x-2">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full border-3 border-white"></div>
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full border-3 border-white"></div>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-600 rounded-full border-3 border-white"></div>
            <div class="w-10 h-10 bg-gradient-to-br from-purple-300 to-purple-500 rounded-full border-3 border-white"></div>
          </div>
          <span class="text-base font-medium">Join 200+ developers waiting</span>
        </div>
      </div>

      <!-- Problem Illustration -->
      <div class="max-w-5xl mx-auto">
        <div class="bg-gray-50 rounded-3xl p-12 border border-gray-100">
          <div class="text-center mb-12">
            <span class="inline-block bg-red-50 text-red-600 px-6 py-3 rounded-2xl text-lg font-semibold border border-red-100">
              Too many apps! 😫
            </span>
          </div>

          <div class="grid grid-cols-2 lg:grid-cols-4 gap-12">
            <div class="text-center">
              <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-sm border border-gray-100">
                <span class="text-3xl">📧</span>
              </div>
              <p class="text-lg font-medium text-gray-700">Gmail</p>
            </div>

            <div class="text-center">
              <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-sm border border-gray-100">
                <span class="text-3xl">💬</span>
              </div>
              <p class="text-lg font-medium text-gray-700">Slack</p>
            </div>

            <div class="text-center">
              <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-sm border border-gray-100">
                <span class="text-3xl">📊</span>
              </div>
              <p class="text-lg font-medium text-gray-700">Linear</p>
            </div>

            <div class="text-center">
              <div class="w-20 h-20 bg-white rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-sm border border-gray-100">
                <span class="text-3xl">💾</span>
              </div>
              <p class="text-lg font-medium text-gray-700">Drive</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Problem Section -->
    <section class="py-24 bg-gray-50" id="problem">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-20">
          <h2 class="text-5xl lg:text-6xl font-bold text-black mb-6">
            The <span class="text-red-500">problem</span> with modern teams
          </h2>
          <p class="text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
            Your team's knowledge is scattered across dozens of apps. Finding information is like searching for a needle in a haystack.
          </p>
        </div>

        <!-- Problem Points -->
        <div class="grid lg:grid-cols-3 gap-16 lg:gap-12">
          <!-- Time Wasted -->
          <div class="text-center">
            <div class="w-24 h-24 bg-red-50 rounded-full mx-auto mb-8 flex items-center justify-center">
              <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl lg:text-3xl font-bold text-black mb-6">Time Wasted</h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              Developers spend 30% of their day just searching for information across different tools
            </p>
          </div>

          <!-- Lost Context -->
          <div class="text-center">
            <div class="w-24 h-24 bg-red-50 rounded-full mx-auto mb-8 flex items-center justify-center">
              <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl lg:text-3xl font-bold text-black mb-6">Lost Context</h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              Important decisions and discussions are buried in Slack threads, emails, and documents
            </p>
          </div>

          <!-- Knowledge Silos -->
          <div class="text-center">
            <div class="w-24 h-24 bg-red-50 rounded-full mx-auto mb-8 flex items-center justify-center">
              <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <h3 class="text-2xl lg:text-3xl font-bold text-black mb-6">Knowledge Silos</h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              Team knowledge lives in isolated apps, making collaboration inefficient
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Story Section -->
    <section class="py-24 bg-white" id="solution">
      <div class="max-w-5xl mx-auto px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-20">
          <h2 class="text-5xl lg:text-6xl font-bold text-black mb-8">
            Sound familiar?
          </h2>
        </div>

        <!-- Story Points -->
        <div class="space-y-8">
          <!-- Calendar Event -->
          <div class="flex items-start space-x-6">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>
            <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed">
              You have a meeting with "Dunsin" in your calendar. Event name: "Dunsin x Martin"
            </p>
          </div>

          <!-- Confusion -->
          <div class="flex items-start space-x-6">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed">
              You have no idea what the meeting is about or who Dunsin is
            </p>
          </div>

          <!-- Frantic Search -->
          <div class="flex items-start space-x-6">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
            </div>
            <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed">
              You frantically search through Slack, Gmail, and your notes trying to find context
            </p>
          </div>

          <!-- Time Wasted -->
          <div class="flex items-start space-x-6">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed">
              You waste 15 minutes before the meeting just trying to prepare
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Solution Demo Section -->
    <section class="py-24 bg-gray-50">
      <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <!-- Chat Interface Demo -->
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
          <!-- Chat Header -->
          <div class="bg-gray-50 px-8 py-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 bg-red-400 rounded-full"></div>
              <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
              <div class="w-3 h-3 bg-green-400 rounded-full"></div>
              <span class="ml-4 text-sm text-gray-500 font-medium">Horizontal Chat</span>
            </div>
          </div>

          <!-- Chat Messages -->
          <div class="p-8 space-y-6">
            <!-- User Message -->
            <div class="flex items-start space-x-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-sm">You</span>
                </div>
              </div>
              <div class="flex-1">
                <div class="bg-purple-500 text-white rounded-2xl rounded-tl-md px-6 py-4 inline-block max-w-4xl">
                  <p class="text-lg">"Why do we have this weird workaround in the payment feature?"</p>
                </div>
              </div>
            </div>

            <!-- Horizontal Response -->
            <div class="flex items-start space-x-4">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                  <span class="text-white font-bold text-sm">H</span>
                </div>
              </div>
              <div class="flex-1">
                <div class="bg-gray-50 rounded-2xl rounded-tl-md px-6 py-6 max-w-5xl">
                  <p class="text-lg text-gray-800 mb-6">
                    Based on your team's history, this workaround was added because of a Stripe API limitation discussed in:
                  </p>

                  <!-- Source Links -->
                  <div class="space-y-4">
                    <!-- GitHub PR -->
                    <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100">
                      <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                      <span class="text-gray-800 font-medium">GitHub PR #247 - "Fix payment processing edge case"</span>
                    </div>

                    <!-- Slack Thread -->
                    <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100">
                      <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                      <span class="text-gray-800 font-medium">Slack #engineering - Thread from March 15</span>
                    </div>

                    <!-- Linear Ticket -->
                    <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100">
                      <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                      <span class="text-gray-800 font-medium">Linear ticket DEV-123 - Payment integration issues</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section id="waitlist" class="py-24 bg-white">
      <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <!-- Main CTA -->
        <div class="text-center mb-16">
          <h2 class="text-5xl lg:text-6xl font-bold text-black mb-8">
            Join the <span class="bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent">revolution</span>
          </h2>
          <p class="text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto mb-12 leading-relaxed">
            Be among the first development teams to experience the future of connected productivity.
          </p>

          <!-- Signup Form -->
          <div class="max-w-2xl mx-auto mb-12">
            <div class="bg-gray-50 rounded-2xl p-8 border border-gray-100">
              <form @submit="handleSubmit" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                  <div class="relative">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                    </svg>
                    <input
                      type="email"
                      v-model="email"
                      placeholder="your.email@company.com"
                      required
                      :disabled="isSubmitting"
                      class="w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all disabled:opacity-50"
                    >
                  </div>
                </div>
                <button
                  type="submit"
                  :disabled="isSubmitting || !email"
                  class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                >
                  {{ isSubmitting ? 'Joining...' : 'Join Waitlist' }}
                </button>
              </form>

              <!-- Success Message -->
              <div v-if="showSuccess" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                <p class="text-green-800 text-center font-medium">
                  🎉 Thanks for joining the waitlist! We'll be in touch soon.
                </p>
              </div>

              <!-- Error Message -->
              <div v-if="showError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-red-800 text-center font-medium">
                  ❌ Something went wrong. Please try again or contact us directly.
                </p>
              </div>
            </div>
          </div>

          <!-- Trust Indicators -->
          <div class="flex items-center justify-center space-x-8 text-gray-500 text-base">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
              <span>200+ developers waiting</span>
            </div>
            <span>•</span>
            <span>No spam, ever</span>
            <span>•</span>
            <span>Early access priority</span>
          </div>
        </div>

        <!-- Benefits Grid -->
        <div class="grid lg:grid-cols-3 gap-12 lg:gap-8">
          <!-- Early Access -->
          <div class="text-center">
            <h3 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent mb-6">
              Early Access
            </h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              Be first to try Horizontal when it launches
            </p>
          </div>

          <!-- Special Pricing -->
          <div class="text-center">
            <h3 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent mb-6">
              Special Pricing
            </h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              50% off your first year subscription
            </p>
          </div>

          <!-- Shape the Product -->
          <div class="text-center">
            <h3 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent mb-6">
              Shape the Product
            </h3>
            <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
              Your feedback will guide our development
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-800 text-white py-16">
      <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <!-- Main Footer Content -->
        <div class="text-center mb-12">
          <!-- Company Name -->
          <h3 class="text-3xl font-bold mb-6">Horizontal</h3>

          <!-- Company Description -->
          <p class="text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed mb-12">
            The everything app for development teams. Connect all your tools, search everything, work faster.
          </p>

          <!-- Footer Links -->
          <div class="flex items-center justify-center space-x-12 mb-12">
            <a href="#privacy" class="text-gray-300 hover:text-white text-lg font-medium transition-colors">
              Privacy
            </a>
            <a href="#terms" class="text-gray-300 hover:text-white text-lg font-medium transition-colors">
              Terms
            </a>
            <a href="#contact" class="text-gray-300 hover:text-white text-lg font-medium transition-colors">
              Contact
            </a>
          </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-slate-700 pt-8">
          <p class="text-center text-gray-400 text-base">
            © 2024 Horizontal. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Ensure gradients work properly */
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
}

/* Custom gradient animations */
@keyframes gradient {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

.animate-gradient {
  background-size: 200% 200%;
  animation: gradient 3s ease infinite;
}

/* Smooth hover transitions */
button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Fix border-3 class */
.border-3 {
  border-width: 3px;
}
</style>
