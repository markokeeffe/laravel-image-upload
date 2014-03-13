<a href="{{ action('MOK\ImageUpload\ImageController@form', $owner) }}"
  data-behavior="imageUpload"
  @foreach ($attrs as $key => $val)
  data-{{ $key }}="{{ $val }}"
  @endforeach
>
  {{ $content }}
</a>
