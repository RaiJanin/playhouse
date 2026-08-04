@extends('layout.app')

@section('title', 'Mimo Play Cafe')

@section('styles')
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-bounce-slow { animation: bounce-slow 3s ease-in-out infinite; }
    </style>
@endsection

@section('main-content')
    @include('components.backdrop')
    <div class="container max-w-full mx-auto">
        @include('ui.partials.header')
        <div class="opacity-100 z-10">
            <div class="relative max-w-full mx-auto sm:p-2 md:p-10 text-center">
                @include('ui.partials.baloons')
                <div class="flex flex-col items-center px-2 sm:px-8 mt-12 sm:mt-2">
                    <div class="flex items-start sm:mb-6">
                        <h1 class="text-[#0d9984] text-4xl md:text-6xl font-extrabold mb-3">
                            Welcome!
                        </h1>
                    </div>
                    <div class="z-20 flex flex-col mb-6">
                        <h1 class="text-2xl sm:text-5xl text-pretty max-w-xl mb-4 font-bold text-gray-700">
                            A fun and creative space where 
                            <span class="text-amber-600 text-xl sm:text-4xl">little imaginations</span> 
                            grow <span class="text-[var(--color-primary-mid-dark)] font-extrabold">BIG</span>
                        </h1>
                    </div>
                    
                    <p class="text-base sm:text-xl sm:w-xl text-[#0d9984] font-medium mb-8">
                        Let your child explore, learn, play, and socialize while you relax and enjoy quality time
                    </p>
                    
                    <div class="flex sm:flex-row sm:justify-evenly gap-4 flex-col">
                        <!-- Start Now Button -->
                        <button 
                            onclick="window.location.href='{{route('playhouse.checkin.source')}}'"
                            class="group relative px-6 py-4 bg-[var(--color-primary)] text-white font-semibold text-xl 
                                    rounded-md shadow-lg overflow-hidden transition-all duration-300 
                                    hover:shadow-xl hover:scale-105 active:scale-95">
                            <span class="relative z-10 flex items-center justify-center gap-3">
                                Check in
                                <span class="transition-transform group-hover:translate-x-1 text-2xl">➔</span>
                            </span>
                        </button>

                        <button hidden
                            onclick="window.location.href='{{route('playhouse.checkout')}}'"
                            class="group relative px-6 py-4 bg-[var(--color-accent)] text-[var(--color-primary-mid-dark)] border border-[var(--color-primary-mid-dark)] font-semibold text-xl 
                                    rounded-md shadow-lg overflow-hidden transition-all duration-300 
                                    hover:shadow-xl hover:scale-105 active:scale-95">
                            <span class="relative z-10 flex items-center justify-center gap-3">
                                Check out
                                <span class="transition-transform group-hover:translate-x-1 text-2xl">➔</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


