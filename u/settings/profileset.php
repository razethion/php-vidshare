<form id="form" method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <div class="input-group">
            <span class="input-group-text">Profile photo</span>
            <input name="file" type="file" class="form-control <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>"
                   id="formFile" aria-describedby="filefeedback"
                   aria-label="Upload" accept="image/png,image/jpeg,image/jpg">
            <div id="filefeedback" class="invalid-feedback">
                <?php if (isset($file_err)) {
                    echo $file_err;
                } ?>
            </div>
        </div>
        <div id="filehelpblock" class="form-text">
            PNG or JPEG. Max 15MB. Will be resized to fit.
        </div>
    </div>
    <div class="mb-3" id="previewdiv">
        <img src="" class="rounded-circle" style="object-fit: cover;" id="preview" width="100px" height="100px"
             alt="preview"/>
        <div>Image preview</div>
    </div>
    <div class="mb-3 input-group">
        <span class="input-group-text">Profile description</span>
        <textarea name="profdesc" class="form-control"
                  aria-label="Profile description"><?php echo (!empty($userprofdata['prof_desc'])) ? $userprofdata['prof_desc'] : ''; ?></textarea>
    </div>
    <div class="border p-3 mb-3 rounded">
        <h6>Social links</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="url-website" class="form-label">Website</label>
                <div class="input-group">
                    <span class="input-group-text" id="url-website-pref">https://</span>
                    <input name="url-web" type="text" class="form-control" id="url-website"
                           value="<?php echo (!empty($userprofjson['web'])) ? $userprofjson['web'] : ''; ?>">
                </div>
            </div>
            <div class="col-md-6 align-self-end mb-3">
                <div class="input-group">
                    <span class="input-group-text" id="url-website-pref">Link title:</span>
                    <input name="url-web-desc" type="text" class="form-control" id="url-website-desc"
                           value="<?php echo (!empty($userprofjson['webdesc'])) ? $userprofjson['webdesc'] : ''; ?>">
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="url-twitter" class="form-label">Twitter</label>
            <div class="input-group">
                <span class="input-group-text" id="urlt-witter-pref">https://twitter.com/</span>
                <input name="url-twitter" type="text" class="form-control" id="url-twitter"
                       value="<?php echo (!empty($userprofjson['twitter'])) ? $userprofjson['twitter'] : ''; ?>">
            </div>
        </div>
        <div class="mb-3">
            <label for="url-telegram" class="form-label">Telegram</label>
            <div class="input-group">
                <span class="input-group-text" id="urlt-witter-pref">https://t.me/</span>
                <input name="url-telegram" type="text" class="form-control" id="url-telegram"
                       value="<?php echo (!empty($userprofjson['telegram'])) ? $userprofjson['telegram'] : ''; ?>">
            </div>
        </div>
        <div class="mb-3">
            <label for="url-discord" class="form-label">Discord</label>
            <div class="input-group">
                <span class="input-group-text" id="urlt-witter-pref">Username#6969</span>
                <input name="url-discord" type="text" class="form-control" id="url-discord"
                       value="<?php echo (!empty($userprofjson['discord'])) ? $userprofjson['discord'] : ''; ?>">
            </div>
        </div>
        <div class="mb-3">
            <label for="url-fetlife" class="form-label">FetLife</label>
            <div class="input-group">
                <span class="input-group-text" id="urlt-witter-pref">https://fetlife.com/users/</span>
                <input name="url-fetlife" type="text" class="form-control" id="url-fetlife"
                       value="<?php echo (!empty($userprofjson['fetlife'])) ? $userprofjson['fetlife'] : ''; ?>">
            </div>
        </div>
    </div>
    <button class="btn btn-danger" type="submit">Submit</button>
</form>

<script>
    previewdiv.style.display = "none";
    formFile.onchange = evt => {
        const [file] = formFile.files
        if (file) {
            preview.src = URL.createObjectURL(file);
            previewdiv.style.display = "block"
        }
    }
</script>