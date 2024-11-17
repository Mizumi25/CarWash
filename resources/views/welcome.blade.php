<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Car Wash Reservation Portal</title>

        <!-- Fonts -->
        <!-- <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" /> -->

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js' ,'resources/js/gsap.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50 w-[100vw]">
      @livewire('wire-elements-modal')
        <div class="bg-[#4B4B4B] text-white/50 dark:bg-black dark:text-white/50 h-full w-full flex justify-center flex-col items-center">
                <header class="navigationWelcome grid grid-cols-2 items-center place-items-center w-full h-[6rem] fixed top-0 left-0 z-50">
                  <span>
                    <h1 class="text-2xl text-cyan-200">Car Wash Reservation</h1>
                  </span>
                    @if (Route::has('login'))
                        <nav class="flex flex-1 justify-center w-full">
                          <a
                                    href=""
                                    class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    FAQs
                                </a>
                          <a
                                    href=""
                                    class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    Testimonials
                                </a>
                          <a
                                    href=""
                                    class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    Services
                                </a>
                          <a
                                    href=""
                                    class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    About
                          </a>
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white bg-[#425FC7]"
                                >
                                    Panel
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="border-white border-2 rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                >
                                    LogIn
                                </a>

                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="rounded-md px-3 py-2 text-white ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                    >
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </header>
         

                    <main id="Welcome" class="w-full h-full flex justify-center items-center flex-col">
                        <section id="section1Welcome" class="w-full h-[100vh] flex relative">
                          
                          <div class="slides-container">
                            <div class="slides-inner">
                              <div class="slide has-before"><img class="w-full h-full bg-{100%} object-cover" src="{{ asset('images/hero1.jpeg') }}" alt="Hero 1"></div>
                              <div class="slide has-before"><img class="w-full h-full bg-{100%} object-cover" src="{{ asset('images/hero2.jpeg') }}" alt="Hero 2"></div>
                              <div class="slide has-before"><img class="w-full h-full bg-{100%} object-cover" src="{{ asset('images/hero3.jpeg') }}" alt="Hero 3"></div>
                              <div class="slide has-before"><img class="w-full h-full bg-{100%} object-cover" src="{{ asset('images/hero4.jpeg') }}" alt="Hero 4"></div>
                              <div class="slide has-before"><img class="w-full h-full bg-{100%} object-cover" src="{{ asset('images/hero5.jpeg') }}" alt="Hero 5"></div>
                            </div>
                          </div>
                          
                          <div class="controls absolute top-[50%] left-0 flex justify-between items-center flex-col text-white z-[50]">
                            <button id="prevButton"><i class="fa-solid fa-arrow-up-long"></i></button>
                            <div id="no">0/5</div>
                            <button id="nextButton"><i class="fa-solid fa-arrow-down-long"></i></i></button>
                          </div>
                          
                            <div class="absolute bottom-[10%] text-white left-[5%] bg-gray-700 grid grid-cols-3 gap-6 z-[60] place-items-center items-center rounded-3xl">
                                <div class="w-full h-full px-6 py-6"><span class="font-bold">Location:</span>Gingoog City</div>
                                <div class="w-full h-full px-6 py-6"><span class="font-bold">Contact No.:</span>+63 9241 302 1828</div>
                                  <button type="button" class="bg-[#ADD8E6] px-6 py-6 text-black rounded"
                                    @auth
                                        onclick="Livewire.dispatch('openModal', { component: 'book-modal', arguments: { user: {{ auth()->user()->id }} }})"
                                    @else
                                        onclick="window.location.href='{{ route('login') }}'"
                                    @endauth>
                                    Make A Booking
                                </button>
                                
                            </div>
                        </section>
                        <section id="Welcome2" class="w-full h-[150vh] flex justify-center items-center flex-col relative overflow-hidden">
                            <div class="h-[7rem] w-full top-0 bg-[#ADD8E6] absolute p-[1rem]">
                              <div class="w-full h-full border-2 border-white flex justify-center items-center text-white">
                                <h2>Scroll for more Informations</h2>
                              </div>
                            </div>
                              
                             <div class="main-content absolute top-[50%] left-[50%] transform translate-x-[-50%] translate-y-[-50%] flex flex-col items-center">
                                <div class="copy my-[2em] flex flex-col justify-center items-center">
                                  <div class="line relative my-[0.5em] max-w-max h-[40px]" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);">
                                  <p class="relative text-[20px] transform translate-y-[30px]">Scheduling your car wash has never been simpler</p>
                                </div>
                                  <div class="line relative my-[0.5em] max-w-max h-[40px]" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);">
                                    <p class="relative text-[20px] transform translate-y-[30px]">Choose your preferred time, and we’ll handle the rest</p>
                                  </div>
                                  <div class="line relative my-[0.5em] max-w-max h-[40px]" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);">
                                    <p class="relative text-[20px] transform translate-y-[30px]">Quality, convenience, and a sparkling finish await you!</p>
                                  </div>
                                </div>
                                
                                 <button type="button" class="opening opacity-0 bg-[#ADD8E6] px-6 py-6 text-black rounded"
                                    @auth
                                        onclick="Livewire.dispatch('openModal', { component: 'book-modal', arguments: { user: {{ auth()->user()->id }} }})"
                                    @else
                                        onclick="window.location.href='{{ route('login') }}'"
                                    @endauth>
                                    Make A Booking
                                </button>
                             </div>

                            
                        </section>
                        <section class="w-full h-[100vh] flex relative"></section>
                        <section class="w-full h-[100vh] flex relative"></section>
                        <section class="w-full h-[100vh] flex relative"></section>
                    </main>

                    <footer class="w-[100vw] h-[50vh] bg-black text-white py-8 text-center text-sm text-black dark:text-white/70">
                        This here is footer contact
                    </footer>
                </div>
            </div>
        </div>
    </body>
</html>
