<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <!-- <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div> -->
        <div class="grid min-h-screen grid-cols-1">
            <div class="relative max-h-screen w-full p-2 max-lg:hidden">
            <img
                src="https://cdn.flyonui.com/fy-assets/blocks/marketing-ui/404/error-5.png"
                alt="404 background"
                class="h-full w-full rounded-2xl"
            />
            <img
                src="https://cdn.flyonui.com/fy-assets/blocks/marketing-ui/404/error-6.png"
                alt="404 illustration"
                class="absolute top-1/2 left-1/2 h-[clamp(300px,40vw,477px)] -translate-x-[42%] -translate-y-1/2"
            />
            </div>
        </div>
    </div>
</x-app-layout>
