<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 pb-4">
        <div class="flex flex-col items-center justify-center w-full text-center">
            <slot name="logo"/>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <slot/>
        </div>
    </div>

</template>
