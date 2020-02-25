{if $status == "error"}<div class="alert alert-danger">

        <strong>{$LANG.clientareaerrors}</strong>
        <ul>
                <li>{$error}</li>
        </ul>

</div>
        {elseif $status == "success"}
        <div class="alert alert-success">

                <strong>{$LANG.clientareasuccess}</strong>
                <ul>
                        <li>Belgeleriniz sisteme yüklenmiştir</li>
                </ul>

        </div>
{/if}
{$output}
