<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        About Us
      </h1>
      <p class="mt-2 text-lg font-medium text-primary-600 dark:text-primary-400">
        About Dr. O Virtual Consultations
      </p>
      <p class="mt-1 text-gray-600 dark:text-gray-300">
        Healthcare that comes to you.
      </p>
    </div>

    <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
      <div class="prose prose-slate dark:prose-invert max-w-none space-y-6">
        <p class="text-gray-600 dark:text-gray-300">
          We understand that accessing quality surgical advice isn't always easy. Long queues, travel costs, time off work, and the anxiety of not knowing whether your concern is serious — these barriers keep too many people from getting the guidance they need.
        </p>
        <p class="text-gray-600 dark:text-gray-300 font-medium">
          That's why Dr. O Virtual Consultations exists.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          Who We Are
        </h2>
        <p class="text-gray-600 dark:text-gray-300">
          Dr. O Virtual Consultations is a telemedicine service founded by Dr. Alfonse Omona, a specialist consultant surgeon with over a decade of experience caring for patients across Uganda. From hernias and breast lumps to trauma injuries and post-operative recovery, Dr. O has helped thousands of patients navigate their surgical concerns with clarity and compassion.
        </p>
        <p class="text-gray-600 dark:text-gray-300">
          Now, that same expertise is available to you — wherever you are.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          What We Do
        </h2>
        <p class="text-gray-600 dark:text-gray-300">
          We provide virtual surgical consultations for patients who need:
        </p>
        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-300">
          <li>Virtual consultation to assess your symptoms and provide differential diagnoses.</li>
          <li>Expert guidance on whether a condition requires surgery</li>
          <li>Second opinions before making major treatment decisions</li>
          <li>Wound assessments and advice on healing</li>
          <li>Post-operative follow-up without unnecessary hospital visits</li>
          <li>Reassurance when you're worried but unsure if it's serious</li>
        </ul>
        <p class="text-gray-600 dark:text-gray-300">
          Whether you're in Kampala, upcountry, or abroad, you can access trusted surgical advice through your phone — no travel, no waiting rooms, no hassle.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          Our Approach
        </h2>
        <p class="text-gray-600 dark:text-gray-300">
          We believe good healthcare starts with listening. Every consultation begins with understanding your story — your symptoms, your concerns, your circumstances. We explain things in plain language, answer your questions honestly, and guide you on the best path forward.
        </p>
        <p class="text-gray-600 dark:text-gray-300">
          When surgery is needed, we'll tell you. When it's not, we'll tell you that too. And when you need in-person care, we'll help you find the right place to go.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          Why &quot;Adding Quality to Life&quot;?
        </h2>
        <p class="text-gray-600 dark:text-gray-300">
          Because that's what good surgical care does. It removes pain. It restores function. It gives you answers. It gives you peace of mind.
        </p>
        <p class="text-gray-600 dark:text-gray-300">
          Whether it's helping you understand a diagnosis, guiding you through recovery, or simply reassuring you that the lump you've been worried about is nothing to fear — we're here to add quality to your life.
        </p>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          Our Promise
        </h2>
        <ul class="list-disc pl-6 space-y-2 text-gray-600 dark:text-gray-300">
          <li><strong class="text-gray-800 dark:text-gray-200">Accessible:</strong> Consultations via texts, audio and video on our web platform/app</li>
          <li><strong class="text-gray-800 dark:text-gray-200">Affordable:</strong> Transparent pricing with no hidden fees</li>
          <li><strong class="text-gray-800 dark:text-gray-200">Confidential:</strong> Your health information stays private and secure</li>
          <li><strong class="text-gray-800 dark:text-gray-200">Honest:</strong> Clear advice, even when the answer is &quot;you need to see someone in person&quot;</li>
        </ul>

        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mt-8">
          Get Started
        </h2>
        <p class="text-gray-600 dark:text-gray-300">
          Have a surgical concern? Send us a message. Describe your symptoms, share photos if relevant, and let's talk about how we can help.
        </p>
        <p class="text-gray-600 dark:text-gray-300">
          Your health matters. Let's take care of it — together.
        </p>

        <p class="text-gray-700 dark:text-gray-200 font-medium border-t border-gray-200 dark:border-gray-700 pt-6 mt-8">
          Dr. O Virtual Consultations — Adding quality to life.
        </p>

        <p class="text-gray-600 dark:text-gray-300 text-sm">
          For questions or support, visit our
          <NuxtLink to="/contact" class="text-primary-600 dark:text-primary-400 hover:underline">
            Contact Us
          </NuxtLink>
          page.
        </p>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'default'
})

useSeoMeta({
  title: 'About Us - Dr. O Virtual Consultations',
  description:
    'Dr. O Virtual Consultations: telemedicine by Dr. Alfonse Omona. Virtual surgical consultations, expert guidance, and care that comes to you — across Uganda and beyond.'
})
</script>
