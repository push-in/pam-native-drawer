package dev.pam.drawer

import android.app.Instrumentation
import android.content.Intent
import android.graphics.Color
import android.os.Build
import android.view.View
import android.view.WindowInsetsController
import android.widget.FrameLayout
import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
class PamDrawerLayoutInstrumentedTest {
    @Test
    fun drawerAdaptsStatusBarIconsAndRestoresThemWhenClosed() {
        val instrumentation = InstrumentationRegistry.getInstrumentation()
        val activity = launchActivity(instrumentation)
        try {
            onMain(instrumentation) {
                val lightStatusBar = WindowInsetsController.APPEARANCE_LIGHT_STATUS_BARS
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    activity.window.insetsController?.setSystemBarsAppearance(
                        lightStatusBar,
                        lightStatusBar,
                    )
                } else {
                    @Suppress("DEPRECATION")
                    activity.window.decorView.systemUiVisibility =
                        activity.window.decorView.systemUiVisibility or
                        View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR
                }
                val drawer = PamDrawerLayout(activity).apply {
                    addView(View(activity))
                    addView(View(activity).apply { setBackgroundColor(Color.rgb(8, 24, 43)) })
                }
                activity.host.addView(drawer)

                drawer.setOpen(true, animated = false)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    assertEquals(
                        0,
                        activity.window.insetsController?.systemBarsAppearance?.and(lightStatusBar),
                    )
                } else {
                    @Suppress("DEPRECATION")
                    assertEquals(
                        0,
                        activity.window.decorView.systemUiVisibility and
                            View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR,
                    )
                }

                drawer.setOpen(false, animated = false)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    assertEquals(
                        lightStatusBar,
                        activity.window.insetsController?.systemBarsAppearance?.and(lightStatusBar),
                    )
                } else {
                    @Suppress("DEPRECATION")
                    assertEquals(
                        View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR,
                        activity.window.decorView.systemUiVisibility and
                            View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR,
                    )
                }
            }
        } finally {
            activity.finish()
        }
    }

    @Test
    fun permanentDrawerStartsBelowTheStatusBar() {
        val instrumentation = InstrumentationRegistry.getInstrumentation()
        val activity = launchActivity(instrumentation)
        try {
            lateinit var drawer: View
            onMain(instrumentation) {
                val layout = PamDrawerLayout(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(
                        FrameLayout.LayoutParams.MATCH_PARENT,
                        FrameLayout.LayoutParams.MATCH_PARENT,
                    )
                    addView(View(activity))
                    drawer = View(activity)
                    addView(drawer)
                    setDrawerType(TYPE_PERMANENT)
                }
                activity.host.addView(layout)
            }

            instrumentation.waitForIdleSync()
            onMain(instrumentation) {
                val expectedTop = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    drawer.rootWindowInsets
                        ?.getInsets(android.view.WindowInsets.Type.statusBars())
                        ?.top ?: 0
                } else {
                    @Suppress("DEPRECATION")
                    drawer.rootWindowInsets?.systemWindowInsetTop ?: 0
                }
                assertTrue(expectedTop > 0)
                assertEquals(
                    expectedTop,
                    (drawer.layoutParams as FrameLayout.LayoutParams).topMargin,
                )
            }
        } finally {
            activity.finish()
        }
    }

    @Test
    fun permanentDrawerKeepsEnginePartitionedChildrenAtTheirAuthoredFrames() {
        val instrumentation = InstrumentationRegistry.getInstrumentation()
        val activity = launchActivity(instrumentation)
        try {
            lateinit var content: View
            lateinit var drawer: View
            onMain(instrumentation) {
                val density = activity.resources.displayMetrics.density
                val parentWidth = (900f * density).toInt()
                val drawerWidth = (240f * density).toInt()
                content = View(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(
                        parentWidth - drawerWidth,
                        1_200,
                    ).apply { leftMargin = drawerWidth }
                }
                drawer = View(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(drawerWidth, 1_200)
                }
                val layout = PamDrawerLayout(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(parentWidth, 1_200)
                    addView(content, 0)
                    addView(drawer, 1)
                    setDrawerWidth(240f)
                    setDrawerType(TYPE_PERMANENT)
                }
                activity.host.addView(layout)
                layout.layout(0, 0, parentWidth, 1_200)
            }

            instrumentation.waitForIdleSync()
            onMain(instrumentation) {
                assertEquals(0f, content.translationX, 0.01f)
                assertEquals(0f, drawer.translationX, 0.01f)
            }
        } finally {
            activity.finish()
        }
    }

    @Test
    fun permanentToFrontTransitionCancelsTheObsoleteContentAnimation() {
        val instrumentation = InstrumentationRegistry.getInstrumentation()
        val activity = launchActivity(instrumentation)
        try {
            lateinit var layout: PamDrawerLayout
            lateinit var content: View
            onMain(instrumentation) {
                content = View(activity)
                layout = PamDrawerLayout(activity).apply {
                    layoutParams = FrameLayout.LayoutParams(1_080, 2_280)
                    addView(content)
                    addView(View(activity))
                    setDrawerType(TYPE_PERMANENT)
                }
                activity.host.addView(layout)
                layout.layout(0, 0, 1_080, 2_280)

                // Mirrors incremental property order after rotating from an
                // expanded permanent drawer back to compact Front.
                layout.setOpen(true, animated = false)
                layout.setOpen(false, animated = true)
                layout.setDrawerType(TYPE_FRONT)
            }

            Thread.sleep(350)
            instrumentation.waitForIdleSync()
            onMain(instrumentation) {
                assertEquals(0f, content.translationX, 0.01f)
            }
        } finally {
            activity.finish()
        }
    }
    private fun launchActivity(instrumentation: Instrumentation): PamDrawerTestActivity =
        instrumentation.startActivitySync(Intent(instrumentation.targetContext, PamDrawerTestActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)) as PamDrawerTestActivity
    private fun onMain(instrumentation: Instrumentation, block: () -> Unit) = instrumentation.runOnMainSync(block)
    private companion object { const val TYPE_PERMANENT = 4; const val TYPE_FRONT = 1 }
}
