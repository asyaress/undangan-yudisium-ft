<!DOCTYPE html>
<html lang="en">
  @include('partials.head')
  <body id="home">
    <div class="page-wrapper">
      @include('partials.preloader')
      @include('partials.header')
      @yield('content')
      @include('partials.footer')
    </div>

    @include('partials.scripts')
  </body>
</html>
