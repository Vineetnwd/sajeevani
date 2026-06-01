import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, FlatList, TouchableOpacity, ActivityIndicator, Alert, Modal, TextInput, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

export default function StockistHome() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [stockistName, setStockistName] = useState('');
  
  // Modal state
  const [modalVisible, setModalVisible] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [courier, setCourier] = useState('');
  const [awb, setAwb] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const API_URL = 'https://praanveda.net/web/api/mr.php'; // Adjust this for local testing as needed

  const loadOrders = async () => {
    try {
      const sId = await AsyncStorage.getItem('userId');
      const name = await AsyncStorage.getItem('userName');
      setStockistName(name || 'Stockist');

      const response = await fetch(`${API_URL}?action=stockist_orders&stockist_id=${sId}`);
      const result = await response.json();
      
      if (result.status === 'success') {
        setOrders(result.data || []);
      } else {
        Alert.alert('Error', result.message || 'Failed to load orders');
      }
    } catch (e) {
      console.error(e);
      Alert.alert('Error', 'Network request failed');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadOrders();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    loadOrders();
  };

  const handleLogout = async () => {
    await AsyncStorage.clear();
    router.replace('/login');
  };

  const openDispatchModal = (order) => {
    setSelectedOrder(order);
    setCourier('');
    setAwb('');
    setModalVisible(true);
  };

  const handleDispatch = async () => {
    if (!courier.trim() || !awb.trim()) {
      Alert.alert('Validation Error', 'Please enter Courier Company and AWB Number');
      return;
    }

    setSubmitting(true);
    try {
      const data = new FormData();
      data.append('order_id', selectedOrder.id.toString());
      data.append('status', 'Dispatched');
      data.append('courier_company', courier);
      data.append('awb_no', awb);

      const res = await fetch(`${API_URL}?action=update_status`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: data
      });
      const result = await res.json();

      if (result.status === 'success') {
        setModalVisible(false);
        loadOrders();
      } else {
        Alert.alert('Error', result.message || 'Failed to dispatch order');
      }
    } catch (e) {
      Alert.alert('Error', 'Network connection failed');
    } finally {
      setSubmitting(false);
    }
  };

  const renderOrder = ({ item }) => {
    const isPending = ['Pending', 'Confirmed'].includes(item.status);
    
    let statusColor = '#6B7280'; // Gray
    if (item.status === 'Confirmed') statusColor = '#3B82F6';
    if (item.status === 'Dispatched') statusColor = '#6366F1';
    if (item.status === 'Delivered') statusColor = '#10B981';

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <View>
            <Text style={styles.orderId}>#DO-{item.id}</Text>
            <Text style={styles.date}>{new Date(item.created_at).toLocaleString()}</Text>
          </View>
          <View style={[styles.badge, { backgroundColor: statusColor + '20' }]}>
            <Text style={[styles.badgeText, { color: statusColor }]}>{item.status}</Text>
          </View>
        </View>

        <View style={styles.cardBody}>
          <Text style={styles.doctorName}>Dr. {item.doctor_name}</Text>
          <Text style={styles.doctorPhone}>{item.doctor_phone}</Text>
          <Text style={styles.itemsText}>{item.item_count} Item(s)</Text>
          
          {item.status === 'Dispatched' && item.courier_company && (
            <View style={styles.trackingBox}>
              <Text style={styles.trackingText}><Text style={{fontWeight: 'bold'}}>Courier:</Text> {item.courier_company}</Text>
              <Text style={styles.trackingText}><Text style={{fontWeight: 'bold'}}>AWB:</Text> {item.awb_no}</Text>
            </View>
          )}
        </View>

        {isPending && (
          <TouchableOpacity 
            style={styles.dispatchBtn} 
            onPress={() => openDispatchModal(item)}
          >
            <Text style={styles.dispatchBtnText}>Mark Dispatched</Text>
          </TouchableOpacity>
        )}
      </View>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor="#064E3B" />
        
        <View style={styles.headerWrapper}>
          <View style={styles.header}>
            <View>
              <Text style={styles.greeting}>Welcome back,</Text>
              <Text style={styles.userName}>{stockistName}</Text>
              <View style={styles.roleBadge}>
                <Text style={styles.roleBadgeText}>Stockist</Text>
              </View>
            </View>
            <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
              <Ionicons name="log-out-outline" size={20} color="#064E3B" />
            </TouchableOpacity>
          </View>
        </View>

        {loading ? (
        <ActivityIndicator size="large" color="#4F46E5" style={{ marginTop: 40 }} />
      ) : (
        <FlatList
          data={orders}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderOrder}
          contentContainerStyle={styles.listContent}
          onRefresh={handleRefresh}
          refreshing={refreshing}
          ListEmptyComponent={
            <Text style={styles.emptyText}>No orders assigned to you yet.</Text>
          }
        />
      )}

      {/* Dispatch Modal */}
      <Modal visible={modalVisible} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Dispatch Order #DO-{selectedOrder?.id}</Text>
            
            <Text style={styles.label}>Courier Company</Text>
            <TextInput
              style={styles.input}
              placeholder="e.g. DTDC, BlueDart"
              value={courier}
              onChangeText={setCourier}
            />

            <Text style={styles.label}>AWB No. (Tracking ID)</Text>
            <TextInput
              style={styles.input}
              placeholder="Tracking Number"
              value={awb}
              onChangeText={setAwb}
            />

            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)} disabled={submitting}>
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.submitBtn} onPress={handleDispatch} disabled={submitting}>
                {submitting ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text style={styles.submitBtnText}>Dispatch</Text>
                )}
              </TouchableOpacity>
            </View>
            </View>
          </View>
        </Modal>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  headerWrapper: {
    backgroundColor: '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
  },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  greeting: { fontSize: 13, color: '#A7F3D0', fontWeight: '500' },
  userName: { fontSize: 22, fontWeight: '800', color: '#FFFFFF', letterSpacing: -0.5, marginTop: 2 },
  roleBadge: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, alignSelf: 'flex-start', marginTop: 6 },
  roleBadgeText: { fontSize: 10, fontWeight: '700', color: '#ECFDF5', textTransform: 'uppercase' },
  logoutBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#D1FAE5', justifyContent: 'center', alignItems: 'center' },
  
  listContent: {
    padding: 16,
  },
  card: {
    backgroundColor: '#FFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
    paddingBottom: 12,
    marginBottom: 12,
  },
  orderId: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
  },
  date: {
    fontSize: 11,
    color: '#9CA3AF',
    marginTop: 2,
  },
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  cardBody: {
    marginBottom: 12,
  },
  doctorName: {
    fontSize: 15,
    fontWeight: '600',
    color: '#374151',
  },
  doctorPhone: {
    fontSize: 13,
    color: '#6B7280',
    marginTop: 2,
  },
  itemsText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#4F46E5',
    marginTop: 6,
  },
  trackingBox: {
    marginTop: 10,
    backgroundColor: '#F9FAFB',
    padding: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  trackingText: {
    fontSize: 12,
    color: '#4B5563',
  },
  dispatchBtn: {
    backgroundColor: '#4F46E5',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  dispatchBtnText: {
    color: '#FFF',
    fontWeight: '700',
    fontSize: 14,
  },
  emptyText: {
    textAlign: 'center',
    marginTop: 40,
    color: '#9CA3AF',
    fontSize: 15,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#FFF',
    borderRadius: 20,
    padding: 24,
    width: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 10,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1F2937',
    marginBottom: 20,
    textAlign: 'center',
  },
  label: {
    fontSize: 13,
    fontWeight: '600',
    color: '#4B5563',
    marginBottom: 6,
  },
  input: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginBottom: 16,
    fontSize: 15,
    color: '#111827',
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    marginTop: 10,
  },
  cancelBtn: {
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 10,
    marginRight: 10,
  },
  cancelBtnText: {
    color: '#6B7280',
    fontWeight: '600',
    fontSize: 15,
  },
  submitBtn: {
    backgroundColor: '#4F46E5',
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    minWidth: 100,
  },
  submitBtnText: {
    color: '#FFF',
    fontWeight: '700',
    fontSize: 15,
  },
});
