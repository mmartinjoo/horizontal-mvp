<script setup>
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
            <button class="bg-gray-900 text-white px-6 py-2.5 rounded-lg text-base font-medium hover:bg-gray-800 transition-colors">
              Join Waitlist
            </button>
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
