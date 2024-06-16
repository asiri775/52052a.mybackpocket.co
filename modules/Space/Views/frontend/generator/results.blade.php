@extends('layouts.empty')
@section('head')
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/fotorama/fotorama.css') }}" />
@endsection
@section('content')
    <div class="container ">
        <div class="row">
            <div class="col">
                <div class="panel col-12 m-0 p-0">
                    <div class="panel-title mb-2 text-center"><strong>{{ __('Description Generator') }}</strong></div>
                    <div class="panel-body">


                        <h6>{{ $space->title }}</h6>
                        <div class="fotorama" data-width="100%" data-thumbwidth="135" data-thumbheight="135"
                            data-thumbmargin="15" data-nav="thumbs" data-allowfullscreen="true">


                            <a href="{{ Modules\Media\Helpers\FileHelper::url($space->banner_image_id, 'large') }}"
                                data-thumb="{{ Modules\Media\Helpers\FileHelper::url($space->banner_image_id, 'thumb') }}"
                                data-alt="{{ __('Banner') }}"></a>

                            @foreach ($space->getGallery(true) as $key => $item)
                                <a href="{{ $item['large'] }}" data-thumb="{{ $item['thumb'] }}"
                                    data-alt="{{ __('Gallery') }}"></a>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-7">{!! $text !!}</div>
                            <div class="col-5">{!! $info !!}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
@section('footer')
    <script type="text/javascript" src="{{ asset('libs/fotorama/fotorama.js') }}"></script>
@endsection
