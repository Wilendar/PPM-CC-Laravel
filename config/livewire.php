<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    |
    | This value sets the root class namespace for Livewire component classes in
    | your application. This value affects component auto-discovery and
    | where you place your Livewire components.
    |
    */
    'class_namespace' => 'App\\Http\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This value sets the path for Livewire component views. This affects
    | file manipulation helper commands like `artisan make:livewire`.
    |
    */
    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The view that will be used as the layout when rendering a single component
    | as an entire page via `Route::get('/post/create', CreatePost::class);`.
    | In this case, the view returned by CreatePost will render into $slot.
    |
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Back Button Cache
    |--------------------------------------------------------------------------
    |
    | This value determines whether the back button cache will be used on pages
    | that contain Livewire. By disabling back button cache, it ensures that
    | the back button shows the correct state of components, instead of
    | potentially stale, cached data.
    |
    | Setting it to "false" will disable back button cache.
    |
    */
    'back_button_cache' => false,

    /*
    |--------------------------------------------------------------------------
    | Render On Redirect
    |--------------------------------------------------------------------------
    |
    | This value determines whether Livewire will run a component's `render`
    | method after a redirect has been triggered using something like
    | `redirect(...)` or `$this->redirect(...)`. If this is disabled,
    | render will only be called again if the page is refreshed.
    |
    */
    'render_on_redirect' => false,

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By default, Livewire will disable broadcasting when the application is
    | in debug mode. If you would like to override this behavior and broadcast
    | events while in debug mode, set this value to true.
    |
    */
    'enable_broadcasting' => false,

    /*
    |--------------------------------------------------------------------------
    | Navigate
    |--------------------------------------------------------------------------
    |
    | By default, Livewire will enable the navigation feature. If you would
    | like to disable it, set this value to false.
    |
    */
    'enable_navigate' => false,
];