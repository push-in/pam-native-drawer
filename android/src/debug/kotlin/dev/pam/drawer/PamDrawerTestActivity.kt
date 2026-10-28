package dev.pam.drawer

import android.app.Activity
import android.os.Bundle
import android.widget.FrameLayout

class PamDrawerTestActivity : Activity() {
    lateinit var host: FrameLayout
        private set
    override fun onCreate(state: Bundle?) {
        super.onCreate(state)
        if (android.os.Build.VERSION.SDK_INT >= 30) window.setDecorFitsSystemWindows(false)
        else @Suppress("DEPRECATION") window.decorView.systemUiVisibility = android.view.View.SYSTEM_UI_FLAG_LAYOUT_STABLE or android.view.View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN or android.view.View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
        host = FrameLayout(this)
        setContentView(host)
    }
}
