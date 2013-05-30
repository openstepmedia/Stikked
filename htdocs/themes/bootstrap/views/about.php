<?php $this->load->view("defaults/header"); ?>

<div class="row">
    <div class="span12">
        <div class="page-header">
            <h1>About</h1>
        </div>
    </div>
    <div class="span12">
        <p><?php echo $this->config->item('site_name'); ?> allows you to easily share code with anyone you wish. Here are some features:</p>

        <ul>
            <li>Easy setup</li>
            <li>Syntaxhighlighting for many languages, including live syntaxhighlighting with CodeMirror</li>
            <li>Paste replies</li>
            <li>Diff view between the original paste and the reply</li>
            <li>An API</li>
            <li>Trending pastes</li>
            <li>Anti-Spam features</li>
            <li>Themes support</li>
            <li>Multilanguage support</li>
        </ul>
    </div>
</div>

<?php $this->load->view("defaults/footer"); ?>
