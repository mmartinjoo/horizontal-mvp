<template>
  <section class="py-24 bg-gradient-to-br from-purple-50 via-blue-50 to-white">
    <!-- Decorative background elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute top-10 right-1/4 w-40 h-40 bg-gradient-to-br from-purple-300/20 to-blue-300/20 rounded-full blur-3xl"></div>
      <div class="absolute bottom-10 left-1/3 w-32 h-32 bg-gradient-to-br from-blue-300/15 to-purple-300/15 rounded-full blur-2xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
      <!-- Section Header -->
      <div class="text-center mb-20">
        <h2 class="text-5xl lg:text-6xl font-bold text-black mb-6">
          Complex questions. <span class="bg-gradient-to-r from-purple-500 to-blue-500 bg-clip-text text-transparent">Simple answers.</span>
        </h2>
        <p class="text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed">
          Engineering teams ask complicated questions that involve lots of context across 5+ different tools.
          <span class="bg-gradient-to-r from-purple-500 to-purple-600 bg-clip-text text-transparent font-semibold">Horizontal makes it simple.</span>
        </p>
      </div>

      <!-- Question Cards Grid -->
      <div class="grid lg:grid-cols-3 gap-8 lg:gap-6">
        <QuestionCard
          v-for="(useCase, index) in useCases"
          :key="index"
          :question="useCase.question"
          :quick-answer="useCase.quickAnswer"
          :sources="useCase.sources"
          :icon-type="useCase.iconType"
        />
      </div>

      <!-- Bottom CTA -->
      <div class="text-center mt-16">
        <p class="text-lg text-gray-600 mb-6">
          Stop wasting hours searching across scattered tools
        </p>
        <a href="#waitlist" class="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white px-8 py-3 rounded-xl text-lg font-semibold hover:from-purple-600 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
          <span>Join Horizontal Now</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import QuestionCard from './QuestionCard.vue'

const useCases = ref([
  {
    question: "Someone had an issue with the new authentication flow. What was it?",
    quickAnswer: "Google SSO integration failing for ACME Ltd. - users can't complete login process",
    iconType: "issue",
    sources: [
      {
        type: "slack",
        title: "Slack #engineering",
        description: "\"Google OAuth callback URL returning 404 - users stuck on login screen\""
      },
      {
        type: "linear",
        title: "Linear AUTH-089",
        description: "SSO login flow broken - OAuth redirect configuration issue"
      },
      {
        type: "github",
        title: "PR #892 Comments",
        description: "\"OAuth client ID environment variable missing in production\""
      },
      {
        type: "google_docs",
        title: "Google Docs",
        description: "Auth system architecture review - SSO integration requirements"
      }
    ]
  },
  {
    question: "Did we task all notification system complaints from the product team?",
    quickAnswer: "Missing 2 tasks: mobile push notifications and email frequency controls",
    iconType: "tasks",
    sources: [
      {
        type: "slack",
        title: "Slack #product-feedback",
        description: "\"Need mobile push controls\" - Sarah from product team"
      },
      {
        type: "linear",
        title: "Linear PLT-162",
        description: "5 out of 7 feedback items have corresponding tasks created"
      },
      {
        type: "google_docs",
        title: "Google Docs",
        description: "Product review meeting - notification system feedback summary"
      },
      {
        type: "github",
        title: "GitHub Issue #789",
        description: "Email notification frequency - enhancement request"
      }
    ]
  },
  {
    question: "Why didn't we include real-time updates on the dashboard?",
    quickAnswer: "Resource constraints & timeline. Pushed to Q2 after backend team capacity review",
    iconType: "scope",
    sources: [
      {
        type: "linear",
        title: "Linear DASH-019",
        description: "Real-time dashboard updates - moved to backlog due to WebSocket complexity"
      },
      {
        type: "slack",
        title: "Slack #engineering",
        description: "\"Backend team at capacity, real-time features need dedicated sprint\""
      },
      {
        type: "google_docs",
        title: "Google Docs",
        description: "Q1 scope decisions - real-time features deferred to Q2 roadmap"
      },
    ]
  }
])
</script>

<style scoped>
.bg-clip-text {
  -webkit-background-clip: text;
  background-clip: text;
}
</style>
