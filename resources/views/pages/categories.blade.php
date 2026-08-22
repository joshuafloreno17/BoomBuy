@extends('layouts.app')

@section('content')

<div class="container py-5">
    <h1 class="mb-4">Categories</h1>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h3>📱 Smartphones</h3>
                <p>Browse our latest smartphones.</p>
                <a href="#" class="btn btn-primary">View Products</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h3>💻 Laptops</h3>
                <p>Find laptops for work, school, and gaming.</p>
                <a href="#" class="btn btn-primary">View Products</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h3>🎧 Accessories</h3>
                <p>Explore useful gadgets and accessories.</p>
                <a href="#" class="btn btn-primary">View Products</a>
            </div>
        </div>

    </div>
</div>

@endsection