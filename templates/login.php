<!-- Flip Login / Create Container -->
<div class="mg_flip-container" >
    <div class="mg_flip-card">

        <!-- Front: Login -->
        <div class="mg_flip-front">
            <div class="mg_login-container">
                <div class="mg_logo">
                    <span class="mg_logo-text"><?php echo esc_html( 'MockGenie' ); ?></span>
                </div>

                <h2 class="mg_heading"><?php echo esc_html__( 'Login to MockGenie', 'mockgenie' ); ?></h2>

                <form action="#" class="mg_login-form">
                    <input type="text" name="mg_username" placeholder="<?php esc_attr_e( 'Username or Email', 'mockgenie' ); ?>" class="mg_input" required>
                    <input type="password" name="mg_password" placeholder="<?php esc_attr_e( 'Password', 'mockgenie' ); ?>" class="mg_input" required>
                    <button type="submit" id="mg_button_login" class="mg_button"><?php esc_html_e( 'Login', 'mockgenie' ); ?></button>
                </form>

                <div class="mg_links" style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <a href="#" id="mg_show-create"><?php esc_html_e( 'Create a new account', 'mockgenie' ); ?></a>
                    <a href="#" id="mg_show-reset" >
                        <?php esc_html_e( 'Forgot Password?', 'mockgenie' ); ?>
                    </a>
                </div>
            </div>
        </div>



        <!-- Back: Create Account -->
        <div class="mg_flip-back">
            <div class="mg_create-container">
                <div class="mg_logo">
                    <span class="mg_logo-text"><?php echo esc_html( 'MockGenie' ); ?></span>
                </div>

                <h2 id="mg_form-heading"><?php esc_html_e( 'Create Account', 'mockgenie' ); ?></h2>
                <p class="mg_form-para"><?php esc_html_e( 'Enter your email and we’ll send you the credentials to generate and edit images inside WordPress.', 'mockgenie' ); ?></p>

                <form action="#" method="post" class="mg_create-form">
                    <input type="email" required name="email" placeholder="<?php esc_attr_e( 'Your Email', 'mockgenie' ); ?>" class="mg_input" required>
                    <button type="submit" id="mg_button_create_reset" class="mg_button"><?php esc_html_e( 'Create Account', 'mockgenie' ); ?></button>
                </form>

                <div class="mg_links">
                    <a href="#" id="mg_show-login"><?php esc_html_e( 'Back to Login', 'mockgenie' ); ?></a>
                </div>
            </div>
        </div>

    </div>
</div>

        

