{{--传入变量--}}
@include('mobile.header',['page_title' => $page_title])

{{--引入nav模板文件--}}
@include('mobile.nav')

<div class="jumbotron">
    <div class="container">
        <h1>Hello, This is a guestbook mobile DEMO </h1>
        <p>This is a template for custom.</p>
    </div>
</div>

<div class="container">
    <form class="form-horizontal" action="{{ route('guestbook.mobile.save') }}" method="post">

        <div class="form-group">
            <div>标题：<input class="form-control" type="text" name="title"></div>
        </div>

        <div class="form-group">
            内容: <textarea class="form-control" rows="3" name="content" placeholder="留言内容"></textarea>
        </div>

        @csrf
        <div class="form-group">
            <div class="col-sm-4 ">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>

    <hr>

    <footer>
        <p>&copy; 2018 dsc X.</p>
    </footer>
</div>

@include('mobile.footer')
