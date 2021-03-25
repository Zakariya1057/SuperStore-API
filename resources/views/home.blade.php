@extends('layouts.app')
@section('content')
<div class="container-fluid">
   <div class="row justify-content-center greybg">
      <div class="col-12 col-lg-8">
         <section class="mb-4 mt-5">
            <div class="row justify-content-center py-5">
               <div class="col-md-6 pl-md-5 pl-lg-4">
                  <h1 class="mb-4 mt-5">The perfect <span class="logo-yellow">shopping</span> experience</h1>
                    <p>
                     Easily organise your shopping list.
                     <br>
                     Find stores nearest to your location.
                     <br>
                     Browse special offers and promotions.
                     <br>
                     Easily view product details and reviews.
                     <br><br>
                     Supported Stores: Real Canadian Superstore, Asda
                    </p>
                  <div class="small-center">

                  <a href="https://apps.apple.com/za/app/superstore-groceries-offers/id1537442192">
                     <button class="btn btn-primary mt-5 download mb-4 small-center">Download</button>
                  </a>
                    
                  </div>
               </div>
               <div class="col-md-6 text-center">
                  <img src="/img/screenshots/iphone/home.png" class="img-fluid w-60" alt="">
               </div>
            </div>
         </section>
      </div>
   </div>
   <div class="row justify-content-center whitebg">
      <div class="col-12 col-lg-8">
         <section class="my-5">
            <div class="row justify-content-center py-5">
               <div class="col-md-6 text-center">
                  <img src="/img/screenshots/iphone/list.png" class="img-fluid w-60" alt="">
               </div>
               <div class="col-md-4 mr-auto">
                  <h3 class="mb-5 mt-5">Grocery List.</h3>
                  <h2>Create your shopping list.</h2>
                  <p class="pt-5">Create a grocery list with all products you like, tick items of as you pick them up. Total price will be calculated using any running promotions for the chosen products. Created lists can also be viewed and edited offline, once you're online they'll be automatically synced.</p>
               </div>
            </div>
         </section>
      </div>
   </div>
   <div class="row justify-content-center greybg">
      <div class="col-12 col-lg-8">
         <section class="my-4">
            <div class="row justify-content-center py-5">
               <div class="col-md-4">
                  <h3 class="mb-5 mt-5">Product.</h3>
                  <h2>Product Details Page</h2>
                  <p class="pt-5">View the product description, images and reviews made by other users as well as any running promotion for this product. If you'd like you can monitor product prices and get notification when the prices change.</p>
               </div>
               <div class="col-md-6 text-center">
                  <img src="/img/screenshots/iphone/product.png" class="img-fluid w-60" alt="">
               </div>
            </div>
         </section>
      </div>
   </div>
   <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
         <section class="my-5">
            <div class="row justify-content-center py-5">
               <div class="col-md-6 text-center">
                  <img src="/img/screenshots/iphone/store-search.png" class="img-fluid w-60" alt="">
               </div>
               <div class="col-md-4 mr-auto">
                  <h3 class="mb-5 mt-5">Stores.</h3>
                  <h2>Find the closest stores to your location.</h2>
                  <p class="pt-5">Based on your current location, see the grocery stores nearest to your location. View store opening hours for today.</p>
               </div>
            </div>
         </section>
      </div>
   </div>
   <div class="row justify-content-center greybg">
      <div class="col-12 col-lg-8">
         <section class="my-4">
            <div class="row justify-content-center py-5">
               <div class="col-md-4">
                  <h3 class="mb-5 mt-5">Review.</h3>
                  <h2>Review Your Favourite Products</h2>
                  <p class="pt-5">Write and read reviews for any products you like. This will help other users when they're deciding wether to purchase it or not.</p>
               </div>
               <div class="col-md-6 text-center">
                  <img src="/img/screenshots/iphone/review.png" class="img-fluid w-60" alt="">
               </div>
            </div>
         </section>
      </div>
   </div>
   <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
         <section class="my-4">
            <div class="row justify-content-center py-5">
               <div class="col-12">
                  <h2 class="text-center">Download the app now.</h2>
               </div>
               <div class="col-12 pt-4">
                  <div class="text-center">
                      <a href="https://apps.apple.com/za/app/superstore-groceries-offers/id1537442192">
                        <img src="/img/app-store.png" class="img-fluid app-logo" alt="Apple Logo">
                      </a>
                  </div>
               </div>
            </div>
         </section>
      </div>
   </div>
</div>
@endsection