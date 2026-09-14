@extends('layouts.public')

@section('title', 'Maison du Café — Slow coffee, warmly served')

@section('content')
    <section class="hero" id="home" aria-labelledby="hero-title">
        <img
            class="hero__image"
            src="{{ asset('images/coffeehouse/hero-coffeehouse.png') }}"
            alt="A handcrafted espresso resting on a marble table in a warm, quiet coffeehouse"
        >
        <div class="hero__veil" aria-hidden="true"></div>
        <div class="coffee-container hero__content">
            <p class="hero__kicker">An independent house of coffee</p>
            <h1 id="hero-title">Coffee worth<br><em>slowing down</em> for.</h1>
            <p class="hero__intro">Thoughtfully roasted beans, careful hands and a room made for lingering a little longer.</p>
            <div class="hero__actions">
                <x-public.button href="#menu">Explore our menu</x-public.button>
                <x-public.button href="#story" variant="ghost">Our story</x-public.button>
            </div>
        </div>
        <p class="hero__note">Small batches · Seasonal origins · Everyday ritual</p>
    </section>

    <section class="welcome-note" aria-label="Our coffee philosophy">
        <div class="coffee-container welcome-note__inner">
            <span aria-hidden="true">✦</span>
            <p>From first light to the last quiet cup, every detail is considered.</p>
            <span aria-hidden="true">✦</span>
        </div>
    </section>

    <section class="coffee-section story" id="story">
        <div class="coffee-container story__grid">
            <figure class="story__image-wrap">
                <img
                    src="{{ asset('images/coffeehouse/artisan-espresso.png') }}"
                    alt="Espresso, roasted coffee beans and an olive branch arranged on warm stone"
                    loading="lazy"
                >
                <figcaption>Simple things, made exceptionally well.</figcaption>
            </figure>

            <div class="story__copy">
                <p class="accent-script">A quieter kind of ritual</p>
                <x-public.section-heading eyebrow="Our house" title="Craft, comfort and a sense of place">
                    <p>We believe a coffeehouse should feel discovered rather than designed: warm light, honest materials and the welcome rhythm of cups meeting saucers.</p>
                </x-public.section-heading>
                <p>Our coffee is selected seasonally and roasted in small batches to keep each origin expressive. The result is a menu grounded in classics, with just enough room for curiosity.</p>
                <a class="text-link" href="#menu">Discover the day’s cups <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="coffee-section menu-preview" id="menu">
        <div class="coffee-container">
            <x-public.section-heading eyebrow="From the bar" title="A short menu, carefully composed" align="center">
                <p>Familiar forms, beautifully balanced — made to order and served without hurry.</p>
            </x-public.section-heading>

            <div class="menu-grid">
                <article class="menu-card">
                    <div class="menu-card__number" aria-hidden="true">01</div>
                    <p class="menu-card__type">Espresso</p>
                    <h3>House Espresso</h3>
                    <p>Dark cacao, toasted almond and a lingering caramel finish.</p>
                    <div class="menu-card__meta"><span>Single origin</span><strong>65</strong></div>
                </article>
                <article class="menu-card menu-card--featured">
                    <p class="menu-card__badge">House favourite</p>
                    <div class="menu-card__number" aria-hidden="true">02</div>
                    <p class="menu-card__type">Milk coffee</p>
                    <h3>Café Crème</h3>
                    <p>Silky milk, rounded espresso and a whisper of raw sugar.</p>
                    <div class="menu-card__meta"><span>Warm or iced</span><strong>78</strong></div>
                </article>
                <article class="menu-card">
                    <div class="menu-card__number" aria-hidden="true">03</div>
                    <p class="menu-card__type">Slow bar</p>
                    <h3>Morning Filter</h3>
                    <p>Clean, fragrant and brewed to reveal the season’s origin.</p>
                    <div class="menu-card__meta"><span>Hand poured</span><strong>82</strong></div>
                </article>
            </div>

            <div class="menu-preview__action">
                <x-public.button href="#specials" variant="outline">View seasonal notes</x-public.button>
            </div>
        </div>
    </section>

    <section class="special" id="specials" aria-labelledby="special-title">
        <div class="coffee-container special__grid">
            <div>
                <p class="section-heading__eyebrow section-heading__eyebrow--light">The seasonal table</p>
                <h2 id="special-title">An afternoon pairing, made for two.</h2>
            </div>
            <p>Two hand-poured coffees with a warm butter madeleine. Available each afternoon while the bake lasts.</p>
            <div class="special__offer">
                <span>Daily</span>
                <strong>14:00–17:00</strong>
            </div>
        </div>
    </section>

    <section class="coffee-section reservation" id="reservation">
        <div class="coffee-container reservation__panel">
            <div class="reservation__ornament" aria-hidden="true">M</div>
            <div>
                <p class="accent-script">Your table is waiting</p>
                <h2>Make room for a slower moment.</h2>
                <p>Join us for an early cup, a long lunch or a candlelit evening coffee.</p>
            </div>
            <x-public.button href="mailto:hello@example.com">Request a table</x-public.button>
        </div>
    </section>
@endsection