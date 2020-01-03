<p>Request URI: {{$request->url}}</p>
<?php echo "<pre>"; print_r($request->inputs); echo "</pre>"; ?>
<hr>
{!! $content !!}