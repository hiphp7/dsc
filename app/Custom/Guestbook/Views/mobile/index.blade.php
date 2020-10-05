{{--传入变量--}}
@include('mobile.header',['page_title' => $page_title])

{{--引入nav模板文件， 若当前目录找不到相应blade 则会去找resources/views目录下blade--}}
@include('mobile.nav')

<div class="jumbotron">
    <div class="container">
        <h1>Hello, This is a guestbook mobile DEMO </h1>
        <p>This is a template for custom.</p>
    </div>
</div>

<div class="container">
    <!-- Example row of columns -->
    <div class="row">

        @foreach($guestbook_list as $v)

        <div class="col-md-4">
            <h2>{{ $v['username'] }}</h2>
            <p>{{ $v['content'] }}</p>
            <p><a class="btn btn-default" href="{{ route('guestbook.mobile.add', ['id' => $v['id']]) }}" role="button">add Guest &raquo;</a></p>
        </div>

        @endforeach

    </div>

    <hr>

    <footer>
        <p>&copy; 2018 dsc X.</p>
    </footer>
</div>

@include('mobile.footer')
