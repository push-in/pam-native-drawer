package dev.pam.drawer

import android.content.Context
import android.view.View
import dev.pam.nativeapp.protocol.WireValue
import dev.pam.nativeapp.views.NativeViewFactory

class DrawerViewFactory : NativeViewFactory {
    override fun create(context: Context, emit: (ByteArray) -> Unit): View = PamDrawerLayout(context)
    override fun update(view: View, properties: Map<String, WireValue>) = Unit
    override fun release(view: View) { (view as PamDrawerLayout).releaseHost() }
}
