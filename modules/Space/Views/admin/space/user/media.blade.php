<div class="space-form-box">
    <div class="panel">
        <div class="panel-title"><strong>{{ __('Media') }}</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6 col-12">
                    <div class="form-group">
                        <label>{{ __('Featured Image') }}</label>
                        {!! \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id) !!}
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    @if (is_default_lang())
                        <div class="form-group">
                            <label class="control-label">{{ __('Banner Image') }}</label>
                            <div class="form-group-image">
                                {!! \Modules\Media\Helpers\FileHelper::fieldUpload('banner_image_id', $row->banner_image_id) !!}
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-md-12 col-12">
                    @if (is_default_lang())
                        <div class="form-group">
                            <label class="control-label">{{ __('Gallery') }}</label>
                            {!! \Modules\Media\Helpers\FileHelper::fieldGalleryUpload('gallery', $row->gallery) !!}
                        </div>
                    @endif
                </div>
                <div class="col-md-12 col-12">
                    @if (is_default_lang())
                        <div class="form-group d-none">
                            <label class="control-label">{{ __('Youtube Video') }}</label>
                            <input type="text" name="video" class="form-control"
                                value="{{ old('video', $row->video) }}" placeholder="{{ __('Youtube link video') }}">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetIds() {
            var ids = [];
            $("#sortableItems .image-item").each(function() {
                ids.push($(this).find(".edit-img").attr("data-id"));
            });
            console.log(ids);
            $("#sortableItems").parent().find('input[type="hidden"]').val(ids.join(","));
        }

        setTimeout(() => {
            $("#sortableItems").sortable({
                sort: function(event, ui) {
                    setTimeout(() => {
                        resetIds();
                    }, 500);
                }
            });
        }, 2000);
    </script>
</div>
