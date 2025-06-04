

<template>
     <Head title="Admin" />
  <div class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
      <div class="text-red-500 mb-4">
        <svg class="mx-auto w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Restricted Access</h1>
      <p class="text-gray-600 mb-6">
        You do not have permission to view this page. Please check your account or contact the administrator.
      </p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="https://lengzem.in" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition">
          Go to Homepage
        </a>
        <a href="https://admin.lengzem.in" class="bg-gray-600 text-white px-5 py-2 rounded-full hover:bg-gray-700 transition">
          Go to Admin Panel
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
// No script logic needed at this time.
// You can define props or composition functions here later if needed.
</script>

<style scoped>
/* Tailwind handles most styles; scoped here for any future additions */
</style>
