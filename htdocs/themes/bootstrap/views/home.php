<?php $this->load->view('defaults/header'); ?>

<div class="row home">
    <div class="span10">
        <div class="content">
            <h1>Latest on the Kodeboard</h1>
            <div class="row">
                <div class="span5 recent">
                    <section>
                        <h3>Most recent code</h3>
                        <ul class="breadcrumb">
                            <li><a href="/trends/">All</a> <span class="divider">/</span></li>
                            <li><a href="/trends/javascript">Javascript</a> <span class="divider">/</span></li>
                            <li><a href="/trends/html">HTML</a> <span class="divider">/</span></li>
                            <li><a href="/trends/ruby">Ruby</a> <span class="divider">/</span></li>
                            <li><a href="/trends/python">Python</a> <span class="divider">/</span></li>
                            <li><a href="/trends/php">PHP</a></li>
                        </ul>
                        <ul class="unstyled">
                        <?php foreach($recent['pastes'] as $item) : ?>
                            <li>
                                <div><a href="<?php echo site_url("view/".$item['pid']); ?>" class="item"><?php echo $item['title'] ?></a></div>
                                <div><a href="/trends/<?php echo $item['lang'] ?>"><code><?php echo $item['lang'] ?></code></a></div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
                <div class="span5 popular">
                    <section>
                    <h3>Popular Code</h3>
                        <ul class="breadcrumb">
                            <li><a href="/trends/">All</a> <span class="divider">/</span></li>
                            <li><a href="/trends/javascript">Javascript</a> <span class="divider">/</span></li>
                            <li><a href="/trends/html">HTML</a> <span class="divider">/</span></li>
                            <li><a href="/trends/ruby">Ruby</a> <span class="divider">/</span></li>
                            <li><a href="/trends/python">Python</a> <span class="divider">/</span></li>
                            <li><a href="/trends/php">PHP</a></li>
                        </ul>
                    <ul class="unstyled">
                    <?php foreach($trends['pastes'] as $item) : ?>
                        <li>
                            <div><a href="<?php echo site_url("view/".$item['pid']); ?>" class="item"><?php echo $item['title'] ?></a></div>
                            <div><a href="/trends/<?php echo $item['lang'] ?>"><code><?php echo $item['lang'] ?></code></a></div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
    <div class="span2 sidebar">
        <?php if($this->tank_auth->is_logged_in()) : ?>
        <h3><a href="/logout">Logout</a></h3>
        <?php else : ?>
        <h3>Login / <a href="/register">Register</a></h3>
        <form action="<?php echo site_url('login') ?>" method="post">
            <label>
                Username:
                <input type="text" name="login" />
            </label>
            <label>
                Password:
                <input type="password" name="password" />
            </label>
            <input type="submit" class="btn btn-primary" value="Login" />
        </form>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('defaults/footer');?>
