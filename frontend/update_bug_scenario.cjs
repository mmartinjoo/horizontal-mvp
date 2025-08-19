const fs = require('fs');

// Read the current App.vue file
let appVue = fs.readFileSync('src/App.vue', 'utf8');

// Find the calendar icon and replace with custom bug SVG
const oldCalendarIcon = `            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
            </div>`;

// Replace with custom bug SVG
const newBugIcon = `            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                  <!-- Custom Bug SVG -->
                  <!-- Bug body -->
                  <ellipse cx="12" cy="13" rx="6" ry="7" fill="currentColor" opacity="0.9"/>
                  
                  <!-- Bug head -->
                  <circle cx="12" cy="7" r="3" fill="currentColor"/>
                  
                  <!-- Bug eyes -->
                  <circle cx="10.5" cy="6.5" r="0.8" fill="white"/>
                  <circle cx="13.5" cy="6.5" r="0.8" fill="white"/>
                  <circle cx="10.5" cy="6.5" r="0.4" fill="black"/>
                  <circle cx="13.5" cy="6.5" r="0.4" fill="black"/>
                  
                  <!-- Bug antennae -->
                  <path d="M9 4.5 L8 2.5 M15 4.5 L16 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <circle cx="8" cy="2.5" r="0.8" fill="currentColor"/>
                  <circle cx="16" cy="2.5" r="0.8" fill="currentColor"/>
                  
                  <!-- Bug legs -->
                  <path d="M6 10 L3 9 M6 13 L3 13 M6 16 L3 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M18 10 L21 9 M18 13 L21 13 M18 16 L21 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  
                  <!-- Bug spots -->
                  <circle cx="10" cy="12" r="0.8" fill="white" opacity="0.7"/>
                  <circle cx="14" cy="14" r="0.6" fill="white" opacity="0.7"/>
                  <circle cx="12" cy="16" r="0.5" fill="white" opacity="0.7"/>
                </svg>
              </div>
            </div>`;

// Replace the icon
appVue = appVue.replace(oldCalendarIcon, newBugIcon);

// Write the updated file
fs.writeFileSync('src/App.vue', appVue);
console.log('Bug SVG icon added successfully!');